<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Process for executing shell commands.
 */
interface ProcessInterface
{
    /**
     * Returns command exit code.
     *
     * @return int
     */
    public function getExitCode();

    /**
     * Returns command output.
     *
     * @return string
     */
    public function getOutput();

    /**
     * Gets the command line to be executed.
     *
     * @return string
     */
    public function getCommandLine();

    /**
     * Runs the process.
     *
     * @return void
     * @throws ProcessException
     */
    public function execute();
}
