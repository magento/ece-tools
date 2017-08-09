<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

interface ShellInterface
{
    /**
     * Runs shell command.
     *
     * @param string $command The command.
     * @param string|null $message The message to be displayed before execution
     * @return string The last line from execution result.
     */
    public function execute(string $command, string $message = null);
}
