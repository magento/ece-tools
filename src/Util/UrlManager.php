<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

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
    const MAGIC_ROUTE = '{default}';

    const PREFIX_SECURE = 'https://';
    const PREFIX_UNSECURE = 'http://';

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

        foreach ($routes as $key => $val) {
            if ($val['type'] !== 'upstream') {
                continue;
            }

            $host = parse_url($val['original_url'], PHP_URL_HOST);
            $originalUrlRegEx = sprintf('/(www)?\.?%s/', preg_quote(self::MAGIC_ROUTE));
            $originalUrl = preg_replace($originalUrlRegEx, '', $host);

            if (strpos($key, self::PREFIX_UNSECURE) === 0) {
                $urls['unsecure'][$originalUrl] = $key;
                continue;
            }

            if (strpos($key, self::PREFIX_SECURE) === 0) {
                $urls['secure'][$originalUrl] = $key;
                continue;
            }
        }

        return $urls;
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

        $this->logger->info('Initializing routes.');

        $urls = $this->parseRoutes($this->environment->getRoutes());

        if (0 === count($urls['unsecure']) && 0 === count($urls['secure'])) {
            throw new \RuntimeException('Expected at least one valid unsecure or secure route. None found.');
        }
        if (0 === count($urls['unsecure'])) {
            $urls['unsecure'] = $urls['secure'];
        }

        if (0 === count($urls['secure'])) {
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
    private function loadStoreBaseUrls()
    {
        if (!$this->storeBaseUrls) {
            try {
                $process = $this->magentoShell->execute('config:show:store-url');

                $baseUrls = json_decode($process->getOutput(), true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($baseUrls)) {
                    $this->storeBaseUrls = $baseUrls;
                }
            } catch (ShellException $e) {
                $this->logger->error('Can\'t fetch store urls. ' . $e->getMessage());
            }
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
