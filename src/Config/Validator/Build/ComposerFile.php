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
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
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
     * @var Manager
     */
    private $manager;

    /**
     * @var array
     */
    private static $map = [
        'laminas/laminas-mvc' => 'Laminas\\Mvc\\Controller\\',
        'zendframework/zend-mvc' => 'Zend\\Mvc\\Controller\\',
    ];

    /**
     * @param FileList $fileList
     * @param File $file
     * @param MagentoVersion $magentoVersion
     * @param Validator\ResultFactory $resultFactory
     * @param Manager $manager
     */
    public function __construct(
        FileList $fileList,
        File $file,
        MagentoVersion $magentoVersion,
        Validator\ResultFactory $resultFactory,
        Manager $manager
    ) {
        $this->fileList = $fileList;
        $this->file = $file;
        $this->magentoVersion = $magentoVersion;
        $this->resultFactory = $resultFactory;
        $this->manager = $manager;
    }

    /**
     * Validates that composer.json has all required configuration for correct deployment.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.3') || $this->magentoVersion->isGreaterOrEqual('2.4.3')) {
                return $this->resultFactory->success();
            }

            $composerJson = json_decode($this->file->fileGetContents($this->fileList->getMagentoComposer()), true);
            $autoloadPsr4 = $composerJson['autoload']['psr-4'] ?? [];
        } catch (UndefinedPackageException $e) {
            return $this->resultFactory->error(
                'Can\'t get magento version: ' . $e->getMessage(),
                '',
                Error::BUILD_COMPOSER_PACKAGE_NOT_FOUND
            );
        } catch (FileSystemException $e) {
            return $this->resultFactory->error(
                'Can\'t read composer.json file: ' . $e->getMessage(),
                '',
                Error::BUILD_CANT_READ_COMPOSER_JSON
            );
        }

        if (array_intersect_key(($autoloadPsr4), array_flip(self::$map))) {
            return $this->resultFactory->success();
        }

        foreach (self::$map as $name => $namespace) {
            if ($this->manager->has($name)) {
                return $this->resultFactory->error(
                    'Required configuration is missed in autoload section of composer.json file.',
                    sprintf(
                        'Add ("%s: "%s") to autoload -> psr-4 section ' .
                        'and re-run "composer update" command locally. ' .
                        'Then commit new composer.json and composer.lock files.',
                        $namespace,
                        'setup/src/Zend/Mvc/Controller/'
                    ),
                    Error::BUILD_COMPOSER_MISSED_REQUIRED_AUTOLOAD
                );
            }
        }

        /**
         * Edge case when none of MVC packages installed.
         */
        return $this->resultFactory->success();
    }
}
