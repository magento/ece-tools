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

    private const DEFAULT_HOST = '127.0.0.1';
    private const DEFAULT_PORT_HTTP = 12202;
    private const DEFAULT_PORT_TCP = 12201;
    private const DEFAULT_PORT_UDP = 12201;
    private const DEFAULT_PATH_HTTP = '/gelf';

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
                    $config['host'] ?? self::DEFAULT_HOST,
                    $config['port'] ?? self::DEFAULT_PORT_HTTP,
                    $config['path'] ?? self::DEFAULT_PATH_HTTP
                );
                if (isset($config['connection_timeout'])) {
                    $transport->setConnectTimeout($config['connection_timeout']);
                }
                break;
            case self::TRANSPORT_TCP:
                $transport = new TcpTransport(
                    $config['host'] ?? self::DEFAULT_HOST,
                    $config['port'] ?? self::DEFAULT_PORT_TCP
                );
                if (isset($config['connection_timeout'])) {
                    $transport->setConnectTimeout($config['connection_timeout']);
                }
                break;
            case self::TRANSPORT_UDP:
                $transport = new UdpTransport(
                    $config['host'] ?? self::DEFAULT_HOST,
                    $config['port'] ?? self::DEFAULT_PORT_UDP,
                    $config['chunk_size'] ?? UdpTransport::CHUNK_SIZE_WAN
                );
                break;
            default:
                throw new LoggerException('Unknown transport type: ' . $type);
        }

        return $transport;
    }
}
