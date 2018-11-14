<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Validates that composer.json has all required configuration for correct deployment.
 */
class ComposerFile implements ValidatorInterface
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param FileList $fileList
     * @param File $file
     * @param MagentoVersion $magentoVersion
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        FileList $fileList,
        File $file,
        MagentoVersion $magentoVersion,
        Validator\ResultFactory $resultFactory
    ) {
        $this->fileList = $fileList;
        $this->file = $file;
        $this->magentoVersion = $magentoVersion;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates that composer.json has all required configuration for correct deployment.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.3')) {
                return $this->resultFactory->success();
            }

            $composerJson = json_decode($this->file->fileGetContents($this->fileList->getMagentoComposer()), true);
            $autoloadPsr4 = $composerJson['autoload']['psr-4'] ?? [];
        } catch (UndefinedPackageException $e) {
            return $this->resultFactory->error('Can\'t get magento version: ' . $e->getMessage());
        } catch (FileSystemException $e) {
            return $this->resultFactory->error('Can\'t read composer.json file: ' . $e->getMessage());
        }

        if (!isset($autoloadPsr4['Zend\Mvc\Controller\\'])) {
            return $this->resultFactory->error(
                'Required configuration is missed in autoload section of composer.json file.',
                'Add ("Zend\\\\Mvc\\\\Controller\\\\": "setup/src/Zend/Mvc/Controller/") to autoload -> psr-4 section' .
                ' and re-run "composer update" command locally. Then commit new composer.json and composer.lock files.'
            );
        }

        return $this->resultFactory->success();
    }
}
