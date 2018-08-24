<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Validates that configuration file exists.
 */
class ConfigFileExists implements ValidatorInterface
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
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param File $file
     * @param FileList $fileList
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        File $file,
        FileList $fileList,
        Validator\ResultFactory $resultFactory
    ) {
        $this->file = $file;
        $this->fileList = $fileList;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Checks if configuration file MAGENTO_ROOT/app/etc/config.php is exists
     *
     * {@inheritdoc}
     */
    public function validate(): Validator\ResultInterface
    {
        $configFile = $this->fileList->getConfig();

        if (!$this->file->isExists($configFile)) {
            $error = 'File app/etc/config.php does not exist';
            $suggestion = implode(
                PHP_EOL,
                [
                    'Please run the following commands:',
                    '1. bin/magento module:enable --all',
                    '2. git add -f app/etc/config.php',
                    '3. git commit -m \'Adding config.php\'',
                    '4. git push'
                ]
            );

            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => $error,
                    'suggestion' => $suggestion
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
