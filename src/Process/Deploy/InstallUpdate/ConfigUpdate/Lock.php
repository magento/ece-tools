<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock\Config as LockConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Lock implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var LockConfig
     */
    private $lockConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LockConfig $lockConfig
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LockConfig $lockConfig,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->lockConfig = $lockConfig;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
    }

    /**
     * Set lock configuration.
     *
     * @inheritdoc
     */
    public function execute()
    {
        /**
         * Since Magento 2.2.5 we can configure lock providers.
         */
        if ($this->magentoVersion->isGreaterOrEqual('2.2.5')) {
            $lockConfig = $this->lockConfig->get();
            $config = $this->configReader->read();
            $config['lock'] = $lockConfig;
            $this->configWriter->create($config);
            $this->logger->info(sprintf('The lock provider "%s" was set.', $lockConfig['provider']));
        }
    }
}
