<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

/**
 * Interface of database connection data factory
 */
interface ConnectionFactoryInterface
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    const CONNECTION_QUOTE_MAIN = 'quote-main';
    const CONNECTION_QUOTE_SLAVE = 'quote-slave';

    const CONNECTION_SALES_MAIN = 'sales-main';
    const CONNECTION_SALES_SLAVE = 'sales-slave';

    public function create(string $connectionType): ConnectionInterface;
}
