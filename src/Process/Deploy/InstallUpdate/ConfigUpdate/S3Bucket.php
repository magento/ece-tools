<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface as StageConfig;
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
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var StageConfig
     */
    private $stageConfig;

    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(
        LoggerInterface $logger,
        SharedConfig $sharedConfig,
        DeployConfig $deployConfig,
        StageConfig $stageConfig,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->sharedConfig = $sharedConfig;
        $this->deployConfig = $deployConfig;
        $this->stageConfig = $stageConfig;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $s3ConfigKey = 'system.default.thai_s3.general';
        $mediaStorageConfigKey = 'system.default.system.media_storage_configuration.media_storage';

        $s3StageConfig = $this->stageConfig->get(StageConfig::VAR_S3_CONFIGURATION);
        $s3EnvConfig = $this->deployConfig->get($s3ConfigKey, []);

        ksort($s3EnvConfig);
        ksort($s3StageConfig);

        $this->flagManager->delete(FlagManager::FLAG_S3_CONFIG_MODIFIED);

        if ($s3EnvConfig != $s3StageConfig) {
            $this->logger->info('Updating S3 Configuration');

            $this->deployConfig->set($s3ConfigKey, $s3StageConfig);
            $this->flagManager->set(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        }

        $s3ModuleEnabled = (bool)$this->sharedConfig->get('modules.Thai_S3');
        $mediaStorage = $this->deployConfig->get($mediaStorageConfigKey);

        if (!empty($this->deployConfig->get($s3ConfigKey, [])) &&
            $s3ModuleEnabled &&
            $mediaStorage != self::MEDIA_STORAGE_S3
        ) {
            $this->logger->info('Updating Media Storage Configuration');

            $this->deployConfig->set($mediaStorageConfigKey, self::MEDIA_STORAGE_S3);
            $this->flagManager->set(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        }
    }
}
