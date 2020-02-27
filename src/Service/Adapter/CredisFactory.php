<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Adapter;

use Credis_Client;

/**
 * Factory for Credis Client
 *
 * @see Credis_Client
 *
 * @codeCoverageIgnore
 */
class CredisFactory
{
    /**
     * @param string $server
     * @param int $port
     * @param int $database
     * @return Credis_Client
     */
    public function create(string $server, int $port, int $database): Credis_Client
    {
        return new Credis_Client(
            $server,
            $port,
            null,
            '',
            $database
        );
    }
}
