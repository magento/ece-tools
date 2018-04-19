<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 *  Writes warning message about deprecation if file build_options.ini exists.
 */
class DeprecatedBuildOptionsIni implements ValidatorInterface
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

            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => sprintf('File %s is deprecated', basename($this->fileList->getBuildConfig())),
                'suggestion' => 'Please use new configuration file ' . basename($this->fileList->getEnvConfig()),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }
}
