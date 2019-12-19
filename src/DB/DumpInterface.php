<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

/**
 * Interface DumpInterface for generating DB dump commands
 *
 * @api
 */
interface DumpInterface
{
    /**
     * Returns DB dump command with necessary connection data and options.
     *
     * @return string
     */
    public function getCommand(): string;
}
