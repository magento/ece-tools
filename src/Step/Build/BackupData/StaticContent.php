<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build\BackupData;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Copies the directory ./pub/static to ./init/pub/static
 */
class StaticContent implements StepInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param FlagManager $flagManager
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        FlagManager $flagManager
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->flagManager->delete(FlagManager::FLAG_REGENERATE);

            if (!$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
                $this->logger->info('SCD not performed during build');

                return;
            }

            $initPubStatic = $this->directoryList->getPath(DirectoryList::DIR_INIT) . '/pub/static';
            $originalPubStatic = $this->directoryList->getPath(DirectoryList::DIR_STATIC);

            $this->cleanInitPubStatic($initPubStatic);
            $this->moveStaticContent($originalPubStatic, $initPubStatic);
        } catch (StepException $e) {
            throw $e;
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Move static content to init directory.
     *
     * @param string $originalPubStatic
     * @param string $initPubStatic
     * @throws StepException
     */
    private function moveStaticContent(string $originalPubStatic, string $initPubStatic): void
    {
        try {
            $this->logger->info('Moving static content to init directory');
            $this->file->rename($originalPubStatic, $initPubStatic);

            /**
             * Workaround directory mounting on deploy phase.
             */
            $this->logger->info('Recreating pub/static directory');
            $this->file->createDirectory($originalPubStatic);
        } catch (FileSystemException $e) {
            $this->copyStaticContent($originalPubStatic, $initPubStatic);
        }
    }

    /**
     * Copying static content to init directory only in case when moving failed
     *
     * @param string $originalPubStatic
     * @param string $initPubStatic
     * @throws StepException
     */
    private function copyStaticContent(string $originalPubStatic, string $initPubStatic): void
    {
        try {
            $this->logger->notice(
                'Can\'t move static content. Copying static content to init directory'
            );
            $this->file->copyDirectory($originalPubStatic, $initPubStatic);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_SCD_COPYING_FAILED, $e);
        }
    }

    /**
     * Clean init pub static folder or create it if doesn't exist.
     *
     * @param string $initPubStatic
     * @throws StepException
     */
    private function cleanInitPubStatic(string $initPubStatic): void
    {
        try {
            if ($this->file->isExists($initPubStatic)) {
                $this->logger->info('Clear ./init/pub/static');
                $this->file->backgroundClearDirectory($initPubStatic);
            } else {
                $this->logger->info('Create ./init/pub/static');
                $this->file->createDirectory($initPubStatic);
            }
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_CLEAN_INIT_PUB_STATIC_FAILED, $e);
        }
    }
}
