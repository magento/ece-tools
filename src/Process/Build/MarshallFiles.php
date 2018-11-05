<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Marshalls required files.
 *
 * {@inheritdoc}
 */
class MarshallFiles implements ProcessInterface
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
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        MagentoVersion $magentoVersion
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Clears var/cache directory.
     * Copying di.xml files for Magento version < 2.2.
     *
     * Magento version 2.1.x won't install without copying di.xml files.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $enterpriseFolder = $magentoRoot . '/app/enterprise';
        $varCache = $magentoRoot . '/var/cache/';

        if ($this->file->isExists($varCache)) {
            $this->file->deleteDirectory($varCache);
        }

        try {
            if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
                return;
            }
        } catch (UndefinedPackageException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->file->copy(
            $magentoRoot . '/app/etc/di.xml',
            $magentoRoot . '/app/di.xml'
        );

        if (!$this->file->isExists($enterpriseFolder)) {
            $this->file->createDirectory($enterpriseFolder, 0777);
        }

        if ($this->file->isExists($magentoRoot . '/app/etc/enterprise/di.xml')) {
            $this->file->copy(
                $magentoRoot . '/app/etc/enterprise/di.xml',
                $magentoRoot . '/app/enterprise/di.xml'
            );
        }
    }
}
