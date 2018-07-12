<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Upload static content to S3 (if configured)
 */
class UploadStaticContent implements ProcessInterface
{
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
        FlagManager $flagManager,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->configReader = $configReader;
        $this->flagManager = $flagManager;
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $modules = (array)$this->config->get('modules');
        $envConfig = $this->configReader->read();

        if (empty($modules['Thai_S3']) || empty($envConfig['system']['default']['thai_s3']['general'])) {
            $this->logger->debug('S3 Module is not enabled or config has not been set.');
            return;
        }

        if (!$this->flagManager->exists(FlagManager::FLAG_S3_CONFIG_MODIFIED)) {
            $this->logger->debug('S3 configuration has not been changed.');
            return;
        }

        $this->logger->notice('Uploading static content to S3 bucket');

        $this->shell->execute('php ./bin/magento s3:storage:export --ansi --no-interaction');

        $this->flagManager->delete(FlagManager::FLAG_S3_CONFIG_MODIFIED);
    }
}
