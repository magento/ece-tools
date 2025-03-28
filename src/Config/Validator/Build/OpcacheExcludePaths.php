<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * Checks that required paths to exclude for OPCache are present.
 */
class OpcacheExcludePaths implements ValidatorInterface
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
     * Checks if php.ini and op-exclude.txt are present, and they contain needed configuration
     *
     * {@inheritdoc}
     */
    public function validate(): Validator\ResultInterface
    {
        $phpIni = $this->fileList->getPhpIni();
        $excludeList = $this->fileList->getOpCacheExcludeList();

        // Checks if files are present
        if (!$this->file->isExists($phpIni) || !$this->file->isExists($excludeList)) {
            return $this->resultFactory->error(
                'File php.ini or op-exclude.txt does not exist',
                'Check if your cloud template contains latest php.ini and op-exclude.txt files',
                Error::WARN_WRONG_OPCACHE_CONFIG
            );
        }

        // Checks if the php.ini file contains correct path to the op-exclude.txt file
        $parsedPhpIni = (array) $this->file->parseIni($phpIni);

        if (!(array_key_exists('opcache.blacklist_filename', $parsedPhpIni)
            && $parsedPhpIni['opcache.blacklist_filename'] == $excludeList)) {
            return $this->resultFactory->error(
                'File php.ini does not contain opcache.blacklist_filename configuration',
                'Check if your cloud template contains latest php.ini configuration file'
                    . ' https://github.com/magento/magento-cloud/blob/master/php.ini',
                Error::WARN_WRONG_OPCACHE_CONFIG
            );
        }

        // Checks if the op-exclude.txt file contains all needed paths to exclude for OPCache
        $diff = array_diff(
            [
                '/app/*/app/etc/config.php',
                '/app/*/app/etc/env.php',
                '/app/app/etc/config.php',
                '/app/app/etc/env.php',
                '/app/etc/config.php',
                '/app/etc/env.php'
            ],
            explode(PHP_EOL, (string) $this->file->fileGetContents($excludeList))
        );

        if (!empty($diff)) {
            return $this->resultFactory->error(
                'File op-exclude.txt does not contain required paths to exclude for OPCache',
                'Check if your op-exclude.txt contains the next paths:' . PHP_EOL
                    . implode(PHP_EOL, $diff),
                Error::WARN_WRONG_OPCACHE_CONFIG
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
