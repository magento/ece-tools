<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns count of Cpu threads.
 */
class Cpu
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     */
    public function __construct(ShellInterface $shell, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->logger = $logger;
    }

    /**
     * Returns count of CPU threads
     *
     * @return int
     */
    public function getThreadsCount(): int
    {
        try {
            $threadCount = (int)$this->shell->execute('grep -c processor /proc/cpuinfo');
        } catch (ShellException $e) {
            $this->logger->error('Can\'t get system processor count: ' . $e->getMessage());
            $threadCount = 1;
        }

        return $threadCount;
    }
}
