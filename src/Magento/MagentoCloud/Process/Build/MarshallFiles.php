<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        $this->file->clearDirectory($magentoRoot . '/generated/code/');
        $this->file->clearDirectory($magentoRoot . '/var/metadata/');
        $this->file->deleteDirectory($magentoRoot . '/var/cache/');

        try {
            $this->file->copy(
                $magentoRoot . '/app/etc/di.xml',
                $magentoRoot . '/app/di.xml'
            );

            $enterpriseFolder = $magentoRoot . '/app/enterprise';

            if (!$this->file->isExists($enterpriseFolder)) {
                $this->file->createDirectory($enterpriseFolder, 0777);
            }

            $this->file->copy(
                $magentoRoot . '/app/etc/enterprise/di.xml',
                $magentoRoot . '/app/enterprise/di.xml'
            );
        } catch (FileSystemException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
