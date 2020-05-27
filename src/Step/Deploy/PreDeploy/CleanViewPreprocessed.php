<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Cleans the var/view_preprocessed directory
 * when the deployment variable SKIP_COPYING_VIEW_PREPROCESSED_DIR is true
 */
class CleanViewPreprocessed implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param GlobalConfig $globalConfig
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        GlobalConfig $globalConfig
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Clean the dir var/view_preprocessed
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            if (!$this->globalConfig->get(GlobalConfig::VAR_SKIP_HTML_MINIFICATION)) {
                return;
            }

            $this->logger->info('Skip copying directory ./var/view_preprocessed.');
            $this->logger->info('Clearing ./var/view_preprocessed');
            $viewPreprocessedPath = $this->directoryList->getPath(DirectoryList::DIR_VIEW_PREPROCESSED);
            $this->file->backgroundClearDirectory($viewPreprocessedPath);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_VIEW_PREPROCESSED_CLEAN_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
