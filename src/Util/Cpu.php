<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * Returns count of CPU threads.
     * Returns 1 if can't read count of cup threads from /proc/cpuinfo.
     *
     * @return int
     */
    public function getThreadsCount(): int
    {
        try {
            $threadCount = (int)$this->shell->execute('grep -c processor /proc/cpuinfo')[0] ?? 1;
        } catch (ShellException $e) {
            $this->logger->error('Can\'t get system processor count: ' . $e->getMessage());
            $threadCount = 1;
        }

        return $threadCount;
    }

    /**
     * Returns count of CPU cores.
     * Assuming that virtualization always enabled and physical core has two threads.
     *
     * @return int value always >= 1
     */
    public function getCoreCount(): int
    {
        return max(floor($this->getThreadsCount() / 2), 1);
    }
}
