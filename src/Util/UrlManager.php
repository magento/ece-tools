<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;

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

    /**1
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
     * @param array $routes from environment variable MAGENTO_CLOUD_ROUTES
     * @return array
     */
    public function parseRoutes(array $routes) : array
    {

        $urls = ['secure' => [], 'unsecure' => []];
        foreach ($routes as $key => $val) {
            if ($val['type'] !== 'upstream') {
                continue;
            }

            $urlParts = parse_url($val['original_url']);
            $originalUrl = str_replace(self::MAGIC_ROUTE, '', $urlParts['host']);

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
     * @throws \RuntimeException if no valid secure or unsecure route found
     */
    public function getUrls()
    {
        if ($this->urls !== null) {
            return $this->urls;
        }

        $this->logger->info('Initializing routes.');

        $this->urls = $this->parseRoutes($this->environment->getRoutes());

        if (!count($this->urls['unsecure']) && !count($this->urls['secure'])) {
            throw new \RuntimeException("Expected at least one valid unsecure or secure route. None found.");
        }
        if (!count($this->urls['unsecure'])) {
            $this->urls['unsecure'] = $this->urls['secure'];
        }

        if (!count($this->urls['secure'])) {
            $this->urls['secure'] = str_replace(self::PREFIX_UNSECURE, self::PREFIX_SECURE, $this->urls['unsecure']);
        }

        $this->logger->info(sprintf('Routes: %s', var_export($this->urls, true)));

        return $this->urls;
    }

    public function getSecureUrls()
    {
        return $this->getUrls()['secure'] ?? [];
    }

    public function getUnSecureUrls()
    {
        return $this->getUrls()['unsecure'] ?? [];
    }
}
