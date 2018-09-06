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
     * Runs shell command.
     *
     * @param string $command The command.
     * @param string|array $args Arguments to pass
     * @return array The output of command.
     * @throws ShellException If command was executed with error
     */
    public function execute(string $command, $args = []): array;
}
