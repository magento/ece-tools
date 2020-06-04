<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;

/**
 * Removes flags which set during the deploy phase.
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
     * @param Manager $manager
     * @param File $fileDriver
     * @param FileList $fileList
     */
    public function __construct(
        Manager $manager,
        File $fileDriver,
        FileList $fileList
    ) {

        $this->manager = $manager;
        $this->fileDriver = $fileDriver;
        $this->fileList = $fileList;
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
            $this->fileDriver->deleteFile($this->fileList->getCloudErrorLog());
        } catch (\Exception $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
