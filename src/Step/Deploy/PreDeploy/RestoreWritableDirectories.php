<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Psr\Log\LoggerInterface;

/**
 * Restoring writable directories.
 */
class RestoreWritableDirectories implements StepInterface
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
     * @inheritdoc
     */
    public function execute()
    {
        try {
            foreach ($this->recoverableDirectoryList->getList() as $dirOptions) {
                $this->buildDirCopier->copy(
                    $dirOptions[RecoverableDirectoryList::OPTION_DIRECTORY],
                    $dirOptions[RecoverableDirectoryList::OPTION_STRATEGY]
                );
            }

            // Restore mounted directories.
            $this->logger->notice('Recoverable directories were copied back.');
            $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
