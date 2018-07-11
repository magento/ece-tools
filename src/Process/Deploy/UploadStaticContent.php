<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class UploadStaticContent implements ProcessInterface
{
    const MEDIA_STORAGE_S3 = 2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SharedConfig
     */
    private $config;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ShellInterface
     */
    private $shell;

    public function __construct(
        LoggerInterface $logger,
        SharedConfig $config,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        FlagManager $flagManager,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->flagManager = $flagManager;
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $modules = (array)$this->config->get('modules');

        if (empty($modules['Thai_S3'])) {
            return;
        }

        $envConfig = $this->configReader->read();

        $mediaStorage = $envConfig['system']['default']['system']['media_storage_configuration']['media_storage'] ?? null;

        // Media storage has already been configured to use S3 and nothing in the config has changed.
        if (
            $mediaStorage == self::MEDIA_STORAGE_S3 &&
            !$this->flagManager->exists(FlagManager::FLAG_S3_CONFIG_MODIFIED)
        ) {
            return;
        }

        $this->logger->notice('Uploading static content to S3 bucket');

        $this->shell->execute('php ./bin/magento s3:storage:export --ansi --no-interaction');

        $envConfig['system']['default']['system']['media_storage_configuration']['media_storage'] = self::MEDIA_STORAGE_S3;

        $this->configWriter->create($envConfig);
    }
}
