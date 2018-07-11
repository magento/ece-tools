<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface as DeployConfig;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

class S3Bucket implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var DeployConfig
     */
    private $stageConfig;

    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(
        LoggerInterface $logger,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        DeployConfig $stageConfig,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->stageConfig = $stageConfig;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $s3StageConfig = $this->stageConfig->get(DeployConfig::VAR_S3_CONFIGURATION);

        $envConfig = $this->configReader->read();

        $s3EnvConfig = $envConfig['system']['default']['thai_s3']['general'] ?? [];

        asort($s3EnvConfig);
        asort($s3StageConfig);

        $this->flagManager->delete(FlagManager::FLAG_S3_CONFIG_MODIFIED);

        if ($s3EnvConfig == $s3StageConfig) {
            return;
        }

        $this->logger->info('Updating S3 Configuration');

        $envConfig['system']['default']['thai_s3']['general'] = $s3StageConfig;
        $this->flagManager->set(FlagManager::FLAG_S3_CONFIG_MODIFIED);

        $this->configWriter->create($envConfig);
    }
}
