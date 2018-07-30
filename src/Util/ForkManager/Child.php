<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util\ForkManager;

/**
 * Utility class for child processes created by ForkManager
 */
class Child
{
    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $command;

    /**
     * @var integer
     */
    private $pid;

    /**
     * @var boolean
     */
    private $running;

    /**
     * @var integer
     */
    private $exitStatus;

    public function __construct(int $pid, string $command, string $description)
    {
        $this->pid = $pid;
        $this->command = $command;
        $this->description = $description;
        $this->running = true;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getDesription()
    {
        return $this->description;
    }

    /**
     * Returns true if the child is still running, false it it has exited.
     * @return bool
     */
    public function isStillRunning()
    {
        if (! $this->running) {
            return false;
        }
        $pid = pcntl_waitpid($this->pid, $status, WNOHANG);
        if ($pid === $this->pid) {
            $this->running = false;
            $this->exitStatus = pcntl_wexitstatus($status);
        }
        return $this->running;
    }
}
