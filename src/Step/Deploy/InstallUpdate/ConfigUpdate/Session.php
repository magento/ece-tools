<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use Magento\MagentoCloud\Step\StepException;
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
        try {
            $config = $this->configReader->read();
            $sessionConfig = $this->sessionConfig->get();

            if (!empty($sessionConfig)) {
                $this->logger->info('Updating session configuration.');
                $config['session'] = $sessionConfig;
            } else {
                $this->logger->info('Removing session configuration from env.php.');
                $config['session'] = ['save' => 'db'];
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->configWriter->create($config);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE, $e);
        }
    }
}
