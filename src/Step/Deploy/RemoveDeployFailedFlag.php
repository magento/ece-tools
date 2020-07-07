<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;

/**
 * Removes flags and files which could be set during the previous deploy phase.
 */
class RemoveDeployFailedFlag implements StepInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ReaderInterface
     */
    private $errorReader;

    /**
     * @param Manager $manager
     * @param File $fileDriver
     * @param FileList $fileList
     * @param ReaderInterface $errorReader
     */
    public function __construct(
        Manager $manager,
        File $fileDriver,
        FileList $fileList,
        ReaderInterface $errorReader
    ) {
        $this->manager = $manager;
        $this->fileDriver = $fileDriver;
        $this->fileList = $fileList;
        $this->errorReader = $errorReader;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $this->manager->delete(Manager::FLAG_DEPLOY_HOOK_IS_FAILED);
            $this->manager->delete(Manager::FLAG_IGNORE_SPLIT_DB);
            $this->manager->delete(Manager::FLAG_ENV_FILE_ABSENCE);

            if ($this->isNeedToRemoveCloudErrorLog()) {
                $this->fileDriver->deleteFile($this->fileList->getCloudErrorLog());
            }
        } catch (\Exception $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Checks if cloud.error.log file should be removed.
     * Returns true if file contains not only errors from the build phase
     *
     * @return bool
     * @throws FileSystemException
     */
    private function isNeedToRemoveCloudErrorLog(): bool
    {
        $errors = $this->errorReader->read();
        foreach ($errors as $error) {
            if (isset($error['stage']) && $error['stage'] != 'build') {
                return true;
            }
        }

        return false;
    }
}
