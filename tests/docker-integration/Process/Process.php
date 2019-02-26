<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

use Illuminate\Config\Repository;

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
        $callback = $callback ?? function ($type, $buffer) {
                echo $buffer;
            };

        return parent::run($callback);
    }
}
