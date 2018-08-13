<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Wrapper for running commands through bin/magento
 */
class ExecBinMagento implements ShellInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var array
     */
    private $defaultArgs = ['--ansi', '--no-interaction'];

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Run a bin/magento command
     *
     * @param string $command
     * @param array|string $args additional args
     * @throws \RuntimeException If command was executed with error
     * @return array The output of command
     */
    public function execute(string $command, $args = [])
    {
        $args = array_map('escapeshellarg', array_merge($this->defaultArgs, array_filter((array)$args)));

        $command = sprintf('php ./bin/magento %s %s', escapeshellarg($command), implode(' ', $args));

        return $this->shell->execute($command);
    }
}
