<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;

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
     * @param ConnectionInterface $connectionData
     *
     * @return string
     */
    public function getCommand(ConnectionInterface $connectionData): string;
}
