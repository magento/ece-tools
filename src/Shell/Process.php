<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs console commands.
 * @codeCoverageIgnore
 */
class Process extends \Symfony\Component\Process\Process implements ProcessInterface
{
    /**
     * Trim new lines from command output
     *
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return trim(parent::getOutput(), PHP_EOL);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            parent::mustRun();
        } catch (ProcessFailedException $e) {
            throw new ProcessException($e->getMessage(), $e->getProcess()->getExitCode());
        }
    }
}
