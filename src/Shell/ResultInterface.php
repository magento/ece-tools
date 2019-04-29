<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 */
interface ResultInterface
{
    /**
     * Returns command exit code
     *
     * @return int
     */
    public function getExitCode(): int;

    /**
     * Returns array of command execute
     *
     * @return array
     */
    public function getOutput(): array;
}
