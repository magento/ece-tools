<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

/**
 * Provides access to system shell operations.
 */
interface ShellInterface
{
    /**
     * Runs shell command.
     *
     * @param string $command The command.
     * @return string The last line from execution result.
     * @throws \RuntimeException If command was executed with error
     */
    public function execute(string $command);

    /**
     * Runs shell command in background.
     *
     * @param string $command The command.
     * @return string The last line from execution result.
     */
    public function backgroundExecute(string $command);
}
