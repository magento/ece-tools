<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

/**
 * @inheritdoc
 */
class Process extends \Symfony\Component\Process\Process
{
    /**
     * @param string $command
     */
    public function __construct(string $command)
    {
        parent::__construct($command, __DIR__ . '/../../..');
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD)
     */
    public function run($callback = null): int
    {
        if (null === $callback) {
            $callback = function ($type, $buffer) {
                fwrite(STDOUT, $buffer);
            };
        }

        return parent::run($callback);
    }
}
