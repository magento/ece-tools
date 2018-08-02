<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Environment;
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
     * @param Environment $environment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
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

        if (0 == count($urls['unsecure']) && 0 == count($urls['secure'])) {
            throw new \RuntimeException('Expected at least one valid unsecure or secure route. None found.');
        }
        if (0 == count($urls['unsecure'])) {
            $urls['unsecure'] = $urls['secure'];
        }

        if (0 == count($urls['secure'])) {
            $urls['secure'] = substr_replace($urls['unsecure'], self::PREFIX_SECURE, 0, strlen(self::PREFIX_UNSECURE));
        }

        $this->logger->debug('Routes: ' . var_export($urls, true));

        return $this->urls = $urls;
    }

    /**
     * @return array
     */
    public function getSecureUrls()
    {
        return $this->getUrls()['secure'] ?? [];
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->getUnSecureUrls()[''];
    }

    /**
     * @return array
     */
    public function getUnSecureUrls()
    {
        return $this->getUrls()['unsecure'] ?? [];
    }
}
