<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Shell;

use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * @inheritdoc
 */
class Shell implements ShellInterface
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $command, $args = []): array
    {
        $rootPathCommand = sprintf(
            'cd %s && %s 2>&1',
            $this->directory,
            $command
        );

        exec(
            $rootPathCommand,
            $output,
            $status
        );

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
