<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory as GelfHandlerFactory;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

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
     * @var GelfHandlerFactory
     */
    private $gelfHandlerFactory;

    /**
     * @param LevelResolver $levelResolver
     * @param LogConfig $logConfig
     * @param GelfHandlerFactory $gelfHandlerFactory
     */
    public function __construct(
        LevelResolver $levelResolver,
        LogConfig $logConfig,
        GelfHandlerFactory $gelfHandlerFactory
    ) {
        $this->levelResolver = $levelResolver;
        $this->logConfig = $logConfig;
        $this->gelfHandlerFactory = $gelfHandlerFactory;
    }

    /**
     * @param string $handler
     * @return HandlerInterface
     * @throws \Exception
     */
    public function create(string $handler): HandlerInterface
    {
        $configuration = $this->logConfig->get($handler);
        $minLevel = $this->levelResolver->resolve($configuration->get('min_level', 'notice'));
        $minLevelStream = $this->levelResolver->resolve($configuration->get('min_level', 'info'));

        switch ($handler) {
            case static::HANDLER_STREAM:
            case static::HANDLER_FILE:
                $handlerInstance =  new StreamHandler($configuration->get('stream'), $minLevelStream);
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
                    true,
                    $configuration->get('logopts', LOG_PID)
                );
                break;
            case static::HANDLER_SYSLOG_UDP:
                $handlerInstance = new SyslogUdpHandler(
                    $configuration->get('host'),
                    $configuration->get('port'),
                    $configuration->get('facility', LOG_USER),
                    $minLevel,
                    true,
                    $configuration->get('ident', 'php')
                );
                break;
            case static::HANDLER_GELF:
                $handlerInstance = $this->gelfHandlerFactory->create($configuration, $minLevel);
                break;
            default:
                throw new \Exception('Unknown type of log handler: ' . $handler);
        }

        return $handlerInstance;
    }
}
