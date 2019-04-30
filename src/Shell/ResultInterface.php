<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Result for shell command.
 */
interface ResultInterface
{
    /**
     * Returns command exit code
     *
     * @return int
     */
    public function getExitCode(): int;

    /**
     * Returns array filled with every line of output from the command
     *
     * @return array
     */
    public function getOutput(): array;
}
