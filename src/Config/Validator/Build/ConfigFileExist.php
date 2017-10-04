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
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Checks if configuration file ROOT/app/etc/config.php is exists
     *
     * {@inheritdoc}
     */
    public function run(): Validator\Result
    {
        $result = new Validator\Result();

        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';

        if (!$this->file->isExists($configFile)) {
            $result->addError('File app/etc/config.php not exists');
            $result->setSuggestion(
                implode(
                    PHP_EOL,
                    [
                        'Please run the following commands',
                        '1. bin/magento module:enable --all',
                        '2. git add -f app/etc/config.php',
                        '3. git commit -a -m \'adding config.php\'',
                        '4. git push'
                    ]
                )
            );
        }

        return $result;
    }
}
