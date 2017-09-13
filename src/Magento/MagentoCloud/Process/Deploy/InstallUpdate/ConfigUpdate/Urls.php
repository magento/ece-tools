<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

class Urls implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param Environment $environment
     * @param Adapter $adapter
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     */
    public function __construct(
        Environment $environment,
        Adapter $adapter,
        LoggerInterface $logger,
        UrlManager $urlManager
    ) {
        $this->environment = $environment;
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->urlManager = $urlManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->isUpdateUrlsEnabled()) {
            $this->logger->info('Skipping URL updates');
            return;
        }

        $this->logger->info('Updating secure and unsecure URLs.');

        foreach ($this->urlManager->getUrls() as $urlType => $urls) {
            foreach ($urls as $route => $url) {
                $prefix = 'unsecure' === $urlType ? UrlManager::PREFIX_UNSECURE : UrlManager::PREFIX_SECURE;
                if (!strlen($route)) {
                    // @codingStandardsIgnoreStart
                    $this->adapter->execute("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and scope_id = '0';");
                    // @codingStandardsIgnoreEnd
                    continue;
                }
                $likeKey = $prefix . $route . '%';
                $likeKeyParsed = $prefix . str_replace('.', '---', $route) . '%';
                // @codingStandardsIgnoreStart
                $this->adapter->execute("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and (value like '$likeKey' or value like '$likeKeyParsed');");
                // @codingStandardsIgnoreEnd
            }
        }
    }
}
