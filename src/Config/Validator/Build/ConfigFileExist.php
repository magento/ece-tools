<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Validates that configuration file exists.
 */
class ConfigFileExist implements ValidatorInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        Validator\ResultFactory $resultFactory
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Checks if configuration file MAGENTO_ROOT/app/etc/config.php is exists
     *
     * {@inheritdoc}
     */
    public function validate(): Validator\Result
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';

        if (!$this->file->isExists($configFile)) {
            $error = 'File app/etc/config.php not exists';
            $suggestion = implode(
                PHP_EOL,
                [
                    'Please run the following commands:',
                    '1. bin/magento module:enable --all',
                    '2. git add -f app/etc/config.php',
                    '3. git commit -a -m \'adding config.php\'',
                    '4. git push'
                ]
            );
        }

        return $this->resultFactory->create($error ?? '', $suggestion ?? '');
    }
}
