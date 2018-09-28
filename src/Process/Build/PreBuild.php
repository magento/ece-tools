<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreBuild implements ProcessInterface
{
    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param BuildInterface $stageConfig
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        BuildInterface $stageConfig,
        LoggerInterface $logger,
        FlagManager $flagManager,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS);

        $generatedCode = $this->directoryList->getGeneratedCode();
        $generatedMetadata = $this->directoryList->getGeneratedMetadata();

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));

        $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);

        if ($this->file->isExists($generatedCode)) {
            $this->logger->info(
                'Generated code exists from an old deployment - clearing it now.',
                ['metadataPath' => $generatedCode]
            );
            $this->file->clearDirectory($generatedCode);
        }

        if ($this->file->isExists($generatedMetadata)) {
            $this->logger->info(
                'Generated metadata exists from an old deployment - clearing it now.',
                ['metadataPath' => $generatedMetadata]
            );
            $this->file->clearDirectory($generatedMetadata);
        }
    }
}
