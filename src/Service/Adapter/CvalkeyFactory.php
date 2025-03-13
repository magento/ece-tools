<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Adapter;

use Cvalkey_Client;

/**
 * Factory for Cvalkey Client
 *
 * @see Cvalkey_Client
 *
 * @codeCoverageIgnore
 */
class CvalkeyFactory
{
    /**
     * @param string $server
     * @param int $port
     * @param int $database
     * @param string|null $password
     * @return Cvalkey_Client
     */
    public function create(string $server, int $port, int $database, string | null $password = null): Cvalkey_Client
    {
        return new Cvalkey_Client(
            $server,
            $port,
            null,
            '',
            $database,
            $password
        );
    }
}
