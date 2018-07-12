<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface as DeployConfig;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Configure environment to use S3 bucket.
 */
class S3Bucket implements ProcessInterface
{
    const MEDIA_STORAGE_S3 = 2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SharedConfig
     */
    private $sharedConfig;

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
        SharedConfig $sharedConfig,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        DeployConfig $stageConfig,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->sharedConfig = $sharedConfig;
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

        ksort($s3EnvConfig);
        ksort($s3StageConfig);

        $this->flagManager->delete(FlagManager::FLAG_S3_CONFIG_MODIFIED);

        if ($s3EnvConfig != $s3StageConfig) {
            $this->logger->info('Updating S3 Configuration');

            $envConfig['system']['default']['thai_s3']['general'] = $s3StageConfig;
            $this->flagManager->set(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        }

        $modules = (array)$this->sharedConfig->get('modules');
        $mediaStorage =
            $envConfig['system']['default']['system']['media_storage_configuration']['media_storage'] ?? null;

        // Media storage has already been configured to use S3 and nothing in the config has changed.
        if (!empty($envConfig['system']['default']['thai_s3']['general']) &&
            !empty($modules['Thai_S3']) &&
            $mediaStorage != self::MEDIA_STORAGE_S3
        ) {
            $this->logger->info('Updating Media Storage Configuration');

            $envConfig['system']['default']['system']['media_storage_configuration']['media_storage'] =
                self::MEDIA_STORAGE_S3;
            $this->flagManager->set(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        }

        $this->configWriter->create($envConfig);
    }
}
