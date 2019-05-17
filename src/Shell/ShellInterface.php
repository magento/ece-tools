<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Provides access to system shell operations.
 */
interface ShellInterface
{
    /**
     * Exit code for successful command execution.
     */
    const CODE_SUCCESS = 0;

    /**
     * Runs shell command.
     *
     * @param string $command The command.
     * @param array $args Arguments to pass
     * @return ProcessInterface The output of command.
     * @throws ShellException If command was executed with error
     */
    public function execute(string $command, array $args = []): ProcessInterface;
}
