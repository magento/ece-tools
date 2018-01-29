<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Gelf;

use Gelf\Transport\AbstractTransport;
use Gelf\Transport\HttpTransport;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\UdpTransport;

class TransportFactory
{
    const TRANSPORT_HTTP = 'http';
    const TRANSPORT_UDP = 'udp';
    const TRANSPORT_TCP = 'tcp';

    /**
     * @param string $type
     * @param array $config
     * @return AbstractTransport
     * @throws \Exception
     */
    public function create(string $type, array $config): AbstractTransport
    {
        switch ($type) {
            case self::TRANSPORT_HTTP:
                $transport = new HttpTransport(
                    $config['host'] ?? null,
                    $config['port'] ?? null,
                    $config['path'] ?? null
                );
                break;
            case self::TRANSPORT_TCP:
                $transport = new TcpTransport(
                    $config['host'] ?? null,
                    $config['port'] ?? null
                );
                break;
            case self::TRANSPORT_UDP:
                $transport = new UdpTransport(
                    $config['host'] ?? null,
                    $config['port'] ?? null,
                    $config['chunk_size'] ?? null
                );
                break;
            default:
                throw new \Exception('Unknown transport type: ' . $type);
        }

        return $transport;
    }
}
