<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Gelf;

use Gelf\Transport\AbstractTransport;
use Gelf\Transport\HttpTransport;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\UdpTransport;
use Magento\MagentoCloud\App\LoggerException;

/**
 * Factory for creating gelf transport instances.
 */
class TransportFactory
{
    public const TRANSPORT_HTTP = 'http';
    public const TRANSPORT_UDP = 'udp';
    public const TRANSPORT_TCP = 'tcp';

    /**
     * @param string $type
     * @param array $config
     * @return AbstractTransport
     *
     * @throws LoggerException
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
                if (isset($config['connection_timeout'])) {
                    $transport->setConnectTimeout($config['connection_timeout']);
                }
                break;
            case self::TRANSPORT_TCP:
                $transport = new TcpTransport(
                    $config['host'] ?? TcpTransport::DEFAULT_HOST,
                    $config['port'] ?? TcpTransport::DEFAULT_PORT
                );
                if (isset($config['connection_timeout'])) {
                    $transport->setConnectTimeout($config['connection_timeout']);
                }
                break;
            case self::TRANSPORT_UDP:
                $transport = new UdpTransport(
                    $config['host'] ?? UdpTransport::DEFAULT_HOST,
                    $config['port'] ?? UdpTransport::DEFAULT_PORT,
                    $config['chunk_size'] ?? UdpTransport::CHUNK_SIZE_WAN
                );
                break;
            default:
                throw new LoggerException('Unknown transport type: ' . $type);
        }

        return $transport;
    }
}
