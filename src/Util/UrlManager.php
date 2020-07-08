<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;

/**
 * Provides an access wrapper to retrieve application URLs.
 */
class UrlManager
{
    private const MAGIC_ROUTE = '{default}';

    private const PREFIX_SECURE = 'https://';
    private const PREFIX_UNSECURE = 'http://';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $urls;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $storeBaseUrls = [];

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        ShellFactory $shellFactory
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->magentoShell = $shellFactory->createMagento();
    }

    /**
     * Reads JSON array of routes and parses them into an array
     *
     * @param array $routes from environment variable MAGENTO_CLOUD_ROUTES
     * @return array
     */
    private function parseRoutes(array $routes): array
    {
        $urls = ['secure' => [], 'unsecure' => []];
        $primaryUrls = [];

        foreach ($routes as $key => $val) {
            if ($val['type'] !== 'upstream') {
                continue;
            }

            $host = parse_url($val['original_url'], PHP_URL_HOST);
            $originalUrlRegEx = sprintf('/(www)?\.?%s/', preg_quote(self::MAGIC_ROUTE, '/'));
            $originalUrl = preg_replace($originalUrlRegEx, '', $host);

            if (strpos($key, self::PREFIX_UNSECURE) === 0) {
                $urls['unsecure'][$originalUrl] = $key;
                if (!empty($val['primary'])) {
                    $primaryUrls['unsecure'][$originalUrl] = $key;
                }
                continue;
            }

            if (strpos($key, self::PREFIX_SECURE) === 0) {
                $urls['secure'][$originalUrl] = $key;
                if (!empty($val['primary'])) {
                    $primaryUrls['secure'][$originalUrl] = $key;
                }
                continue;
            }
        }

        return $primaryUrls ?: $urls;
    }

    /**
     * Parse MagentoCloud routes to more readable format.
     *
     * @throws \RuntimeException if no valid secure or unsecure route found
     */
    public function getUrls(): array
    {
        if ($this->urls !== null) {
            return $this->urls;
        }

        $this->logger->debug('Initializing routes.');

        $urls = $this->parseRoutes($this->environment->getRoutes());

        if (empty($urls['unsecure']) && empty($urls['secure'])) {
            throw new \RuntimeException('Expected at least one valid unsecure or secure route. None found.');
        }
        if (empty($urls['unsecure'])) {
            $urls['unsecure'] = $urls['secure'];
        }

        if (empty($urls['secure'])) {
            $urls['secure'] = substr_replace($urls['unsecure'], self::PREFIX_SECURE, 0, strlen(self::PREFIX_UNSECURE));
        }

        $this->logger->debug('Routes: ' . var_export($urls, true));

        return $this->urls = $urls;
    }

    /**
     * @return array
     */
    public function getSecureUrls(): array
    {
        return $this->getUrls()['secure'] ?? [];
    }

    /**
     * @return array
     */
    public function getUnSecureUrls(): array
    {
        return $this->getUrls()['unsecure'] ?? [];
    }

    /**
     * Returns base url
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null) {
            try {
                $process = $this->magentoShell->execute('config:show:default-url');

                $this->baseUrl = $process->getOutput();
            } catch (ShellException $e) {
                $this->logger->error(
                    'Cannot fetch base URL using the config:show:default-url command. ' .
                    'Instead, using the URL from the MAGENTO_CLOUD_ROUTES variable.'
                );
                $this->logger->debug($e->getMessage());

                $urls = $this->getSecureUrls() ?? $this->getUnSecureUrls();
                $this->baseUrl = $urls[''] ?? reset($urls);
            }
        }

        return $this->baseUrl;
    }

    /**
     * Returns base urls for all stores.
     *
     * @return string[]
     */
    public function getBaseUrls(): array
    {
        $this->loadStoreBaseUrls();

        return $this->storeBaseUrls;
    }

    /**
     * Retrieves base urls for each store and save them into $storeBaseUrls
     */
    private function loadStoreBaseUrls(): void
    {
        if (!$this->storeBaseUrls) {
            try {
                $process = $this->magentoShell->execute('config:show:store-url');

                $baseUrls = json_decode($process->getOutput(), true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($baseUrls)) {
                    $this->storeBaseUrls = $baseUrls;
                }
            } catch (ShellException $e) {
                $this->logger->warning(
                    'Can\'t fetch store urls. ' . $e->getMessage(),
                    ['errorCode' => Error::WARN_CANNOT_FETCH_STORE_URLS]
                );
            }
        }
    }

    /**
     * Gets store base url by store id or store code.
     * Returns an empty string if store url failed to fetch.
     *
     * @param string $storeId store id or store code
     * @return string|null
     */
    public function getStoreBaseUrl(string $storeId): ?string
    {
        try {
            $this->loadStoreBaseUrls();

            if (isset($this->storeBaseUrls[$storeId])) {
                return $this->storeBaseUrls[$storeId];
            }

            $process = $this->magentoShell->execute('config:show:store-url', [$storeId]);

            return $this->storeBaseUrls[$storeId] = $process->getOutput();
        } catch (ShellException $e) {
            $this->logger->warning(
                sprintf('Can\'t fetch store url with store code "%s". %s', $storeId, $e->getMessage()),
                ['errorCode' => Error::WARN_CANNOT_FETCH_STORE_URL]
            );

            return null;
        }
    }

    /**
     * Test if $url is either relative or has the same host as one of the configured base URLs.
     *
     * @param string $url
     * @return bool
     */
    public function isUrlValid(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST) === null || $this->isRelatedDomain($url);
    }

    /**
     * Prepend base URL to relative URLs.
     *
     * @param string $url
     * @return string
     */
    public function expandUrl(string $url): string
    {
        if (parse_url($url, PHP_URL_HOST) === null) {
            return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Checks that host from $url is using in current Magento installation
     *
     * @param string $url
     * @return bool
     */
    public function isRelatedDomain(string $url): bool
    {
        foreach ($this->getBaseUrls() as $baseUrl) {
            if (parse_url($url, PHP_URL_HOST) === parse_url($baseUrl, PHP_URL_HOST)) {
                return true;
            }
        }

        return false;
    }
}
