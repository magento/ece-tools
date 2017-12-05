<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Psr\Log\LoggerInterface;

class RestoreWritableDirectories implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var BuildDirCopier
     */
    private $buildDirCopier;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var RecoverableDirectoryList
     */
    private $recoverableDirectoryList;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param BuildDirCopier $buildDirCopier
     * @param RecoverableDirectoryList $recoverableDirectoryList
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        BuildDirCopier $buildDirCopier,
        RecoverableDirectoryList $recoverableDirectoryList,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->buildDirCopier = $buildDirCopier;
        $this->recoverableDirectoryList = $recoverableDirectoryList;
        $this->directoryList = $directoryList;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->recoverableDirectoryList->getList() as $dirOptions) {
            $this->buildDirCopier->copy(
                $dirOptions[RecoverableDirectoryList::OPTION_DIRECTORY],
                $dirOptions[RecoverableDirectoryList::OPTION_STRATEGY]
            );
        }

        // Restore mounted directories
        $this->logger->info('Recoverable directories were copied back.');

        $magentoRoot = $this->directoryList->getMagentoRoot();

        if ($this->file->isExists($magentoRoot . '/' . Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing var/.regenerate flag');
            $this->file->deleteFile($magentoRoot . '/' . Environment::REGENERATE_FLAG);
        }
    }
}
