<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityManager;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\ForkManager\Child;

/**
 * Utility class for creating forked children and managing their lifecycle
 */
class ForkManager
{

    /**
     * @var Child[]
     */
    private $children = [];

    /**
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param UtilityManager $utilityManager
     */
    public function __construct(
        DirectoryList $directoryList,
        LoggerInterface $logger,
        ShellInterface $shell,
        UtilityManager $utilityManager
    ) {
        $this->targetDirectory = $directoryList->getPath(DirectoryList::DIR_STATIC);
        $this->logger = $logger;
        $this->shell = $shell;
        $this->utilityManager = $utilityManager;
    }

    /**
     * This will fork a child process which will be tracked and managed by this ForkManager object.
     * The argument will be called as a shell command
     */
    public function createForkedChildAndExec(string $command, string $description)
    {
        $pid = pcntl_fork();
        switch ($pid) {
            case 0:   // This is run in the child process
                fclose(STDIN);
                fclose(STDOUT);
                fclose(STDERR);
                // TODO: We need to close all open file descriptors, not just the default ones.
                pcntl_exec('/bin/sh', ['-c', $command]);
                // Note: we shouldn't get to this point unless pcntl_exec failed.
                throw new \RuntimeException("pcntl_exec failed");
                break;
            case -1: // If pcntl_fork failed, we'll just run the command in the this process
                shell_exec($command);
                break;
            default:  // This is the parent process
                $child = new Child($pid, $command, $description);
                $this->children[] = $child;
                break;
        }
    }

    public function waitForChildren()
    {
        $childrenStillRunning = [];

        foreach ($this->children as $child) {
            if ($child->isStillRunning()) {
                $childrenStillRunning[] = $child;
                $this->logger->info(sprintf(
                    'Child process still running: %s\n',
                    $child->getDesription()
                ));
            }
        }
        while (!empty($childrenStillRunning)) {
            /* @var $child Child */
            foreach ($childrenStillRunning as $key => $child) {
                if (!$child->isStillRunning()) {
                    unset($childrenStillRunning[$key]);
                    $this->logger->info(sprintf(
                        'Child process exited: %s\n',
                        $child->getDesription()
                    ));
                }
            }
            if (!empty($childrenStillRunning)) {
                sleep(1);
            }
        }
    }
}
