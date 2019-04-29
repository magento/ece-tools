<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Symfony\Component\Process\Process;

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
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->process->getExitCode();
    }

    /**
     * @return array
     */
    public function getOutput(): array
    {
        return explode(PHP_EOL, $this->process->getOutput());
    }
}
