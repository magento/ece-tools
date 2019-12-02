<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Processes configuration for session.
 */
class Session implements StepInterface
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
     * @var Config
     */
    private $sessionConfig;

    /**
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param Config $sessionConfig
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        Config $sessionConfig
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->sessionConfig = $sessionConfig;
    }

    /**
     * Saves or removes session configuration to env.php.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $config = $this->configReader->read();
        $sessionConfig = $this->sessionConfig->get();

        if (!empty($sessionConfig)) {
            $this->logger->info('Updating session configuration.');
            $config['session'] = $sessionConfig;
        } else {
            $this->logger->info('Removing session configuration from env.php.');
            $config['session'] = ['save' => 'db'];
        }

        $this->configWriter->create($config);
    }
}
