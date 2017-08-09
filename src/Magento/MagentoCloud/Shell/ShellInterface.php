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
     * @return string The last line from execution result.
     */
    public function execute(string $command);
}
