<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Psr\Log\LoggerInterface;

/**
 * Restoring writable directories.
 */
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
     * @var RecoverableDirectoryList
     */
    private $recoverableDirectoryList;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param LoggerInterface $logger
     * @param BuildDirCopier $buildDirCopier
     * @param RecoverableDirectoryList $recoverableDirectoryList
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        BuildDirCopier $buildDirCopier,
        RecoverableDirectoryList $recoverableDirectoryList,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->buildDirCopier = $buildDirCopier;
        $this->recoverableDirectoryList = $recoverableDirectoryList;
        $this->flagManager = $flagManager;
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

        // Restore mounted directories.
        $this->logger->notice('Recoverable directories were copied back.');
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
