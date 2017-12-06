<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFile\RegenerateFlag;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * RestoreWritableDirectories constructor.
     * @param LoggerInterface $logger
     * @param BuildDirCopier $buildDirCopier
     * @param RecoverableDirectoryList $recoverableDirectoryList
     * @param DirectoryList $directoryList
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        LoggerInterface $logger,
        BuildDirCopier $buildDirCopier,
        RecoverableDirectoryList $recoverableDirectoryList,
        DirectoryList $directoryList,
        FlagFilePool $flagFilePool
    ) {
        $this->logger = $logger;
        $this->buildDirCopier = $buildDirCopier;
        $this->recoverableDirectoryList = $recoverableDirectoryList;
        $this->directoryList = $directoryList;
        $this->flagFilePool = $flagFilePool;
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
        $this->flagFilePool->getFlag(RegenerateFlag::KEY)->delete();
    }
}
