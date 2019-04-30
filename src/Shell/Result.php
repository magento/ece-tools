<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Process\Process;

/**
 * Result class for shell command
 */
class Result implements ResultInterface
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function getExitCode(): int
    {
        return (int)$this->process->getExitCode();
    }

    /**
     * @inheritdoc
     */
    public function getOutput(): array
    {
        try {
            $output = explode(PHP_EOL, $this->process->getOutput());
        } catch (LogicException $exception) {
            $output = [];
        }

        return $output;
    }
}
