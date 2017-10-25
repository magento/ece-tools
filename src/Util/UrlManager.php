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
     * Parse MagentoCloud routes to more readable format.
     */
    public function getUrls()
    {
        if ($this->urls !== null) {
            return $this->urls;
        }

        $this->logger->info('Initializing routes.');

        $this->urls = ['secure' => [], 'unsecure' => []];

        $routes = $this->environment->getRoutes();

        foreach ($routes as $key => $val) {
            if ($val['type'] !== 'upstream') {
                continue;
            }

            $urlParts = parse_url($val['original_url']);
            $originalUrl = str_replace(self::MAGIC_ROUTE, '', $urlParts['host']);

            if (strpos($key, self::PREFIX_UNSECURE) === 0) {
                $this->urls['unsecure'][$originalUrl] = $key;
                continue;
            }

            if (strpos($key, self::PREFIX_SECURE) === 0) {
                $this->urls['secure'][$originalUrl] = $key;
                continue;
            }
        }

        if (!count($this->urls['secure'])) {
            $this->urls['secure'] = str_replace('http','https',$this->urls['unsecure']);
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
