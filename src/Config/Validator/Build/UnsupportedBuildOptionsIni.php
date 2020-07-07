<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 *  Writes critical message if file build_options.ini exists.
 */
class UnsupportedBuildOptionsIni implements ValidatorInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param File $file
     * @param FileList $fileList
     * @param ResultFactory $resultFactory
     */
    public function __construct(File $file, FileList $fileList, ResultFactory $resultFactory)
    {
        $this->file = $file;
        $this->fileList = $fileList;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates file build_options.ini existence.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        if ($this->file->isExists($this->fileList->getBuildConfig())) {
            return $this->resultFactory->error(
                sprintf('The %s file is no longer supported.', basename($this->fileList->getBuildConfig())),
                sprintf(
                    'Modify your configuration to specify build options in the %s file',
                    basename($this->fileList->getEnvConfig())
                ),
                Error::WARN_UNSUPPORTED_BUILDS_OPTION_INI
            );
        }

        return $this->resultFactory->success();
    }
}
