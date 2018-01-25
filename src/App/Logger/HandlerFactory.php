<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Gelf\Publisher;
use Gelf\Transport\UdpTransport;
use Monolog\Handler\GelfHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\HandlerInterface;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;

/**
 * The handler factory.
 */
class HandlerFactory
{
    const HANDLER_STREAM = 'stream';
    const HANDLER_FILE = 'file';
    const HANDLER_EMAIL = 'email';
    const HANDLER_SLACK = 'slack';
    const HANDLER_GELF = 'gelf';
    const HANDLER_SYSLOG = 'syslog';
    const HANDLER_SYSLOG_UDP = 'syslog_udp';

    /**
     * @var LevelResolver
     */
    private $levelResolver;

    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @param LevelResolver $levelResolver
     * @param LogConfig $logConfig
     */
    public function __construct(LevelResolver $levelResolver, LogConfig $logConfig)
    {
        $this->levelResolver = $levelResolver;
        $this->logConfig = $logConfig;
    }

    /**
     * @param string $handler
     * @return HandlerInterface
     * @throws \Exception
     */
    public function create(string $handler): HandlerInterface
    {
        $configuration = $this->logConfig->get($handler);
        $minLevel = $this->levelResolver->resolve($configuration->get('min_level', ''));

        switch ($handler) {
            case static::HANDLER_STREAM:
            case static::HANDLER_FILE:
                $handlerInstance =  new StreamHandler($configuration->get('stream'));
                break;
            case static::HANDLER_EMAIL:
                $handlerInstance = new NativeMailerHandler(
                    $configuration->get('to'),
                    $configuration->get('subject', 'Log form Magento Cloud'),
                    $configuration->get('from'),
                    $minLevel
                );
                break;
            case static::HANDLER_SLACK:
                $handlerInstance = new SlackHandler(
                    $configuration->get('token'),
                    $configuration->get('channel', 'general'),
                    $configuration->get('username', 'Slack Log Notifier'),
                    true,
                    null,
                    $minLevel
                );
                break;
            case static::HANDLER_SYSLOG:
                $handlerInstance = new SyslogHandler(
                    $configuration->get('ident'),
                    $configuration->get('facility', LOG_USER),
                    $minLevel,
                    $configuration->get('bubble', true),
                    $configuration->get('logopts', LOG_PID)
                );
                break;
            case static::HANDLER_SYSLOG_UDP:
                $handlerInstance = new SyslogUdpHandler(
                    $configuration->get('host'),
                    $configuration->get('port'),
                    $configuration->get('facility', LOG_USER),
                    $minLevel,
                    $configuration->get('bubble', true),
                    $configuration->get('ident', 'php')
                );
                break;
            case static::HANDLER_GELF:
                $transport = new UdpTransport(
                    $configuration->get('host', UdpTransport::DEFAULT_HOST),
                    $configuration->get('port', UdpTransport::DEFAULT_PORT),
                    $configuration->get('chunk_size', UdpTransport::CHUNK_SIZE_WAN)
                );
                $publisher = new Publisher($transport);

                $handlerInstance = new GelfHandler(
                    $publisher,
                    $minLevel,
                    $configuration->get('bubble', true)
                );
                break;
            default:
                throw new \Exception('Unknown type of log handler: ' . $handler);
        }

        return $handlerInstance;
    }
}
