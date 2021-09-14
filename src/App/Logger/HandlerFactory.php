<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory as GelfHandlerFactory;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

/**
 * The handler factory.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HandlerFactory
{
    private const UNDEFINED_LEVEL = 0;

    public const HANDLER_STREAM = 'stream';
    public const HANDLER_FILE = 'file';
    public const HANDLER_FILE_ERROR = 'file_errors';
    public const HANDLER_EMAIL = 'email';
    public const HANDLER_SLACK = 'slack';
    public const HANDLER_GELF = 'gelf';
    public const HANDLER_SYSLOG = 'syslog';
    public const HANDLER_SYSLOG_UDP = 'syslog_udp';

    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @var GelfHandlerFactory
     */
    private $gelfHandlerFactory;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @param LogConfig $logConfig
     * @param GelfHandlerFactory $gelfHandlerFactory
     * @param GlobalSection $globalConfig
     */
    public function __construct(
        LogConfig $logConfig,
        GelfHandlerFactory $gelfHandlerFactory,
        GlobalSection $globalConfig
    ) {
        $this->logConfig = $logConfig;
        $this->gelfHandlerFactory = $gelfHandlerFactory;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @param string $handler
     * @return AbstractProcessingHandler
     * @throws LoggerException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(string $handler): AbstractProcessingHandler
    {
        try {
            $levelOverride = $this->globalConfig->get(GlobalSection::VAR_MIN_LOGGING_LEVEL);
            $minLevel = !empty($levelOverride) ? $this->normalizeLevel($levelOverride) : self::UNDEFINED_LEVEL;

            $configuration = $this->logConfig->get($handler);
        } catch (ConfigException $exception) {
            throw new LoggerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($customMinLevel = $configuration->get('min_level')) {
            $minLevel = $this->normalizeLevel((string)$customMinLevel);
        }

        try {
            switch ($handler) {
                case static::HANDLER_FILE:
                    $handlerInstance = new StreamHandler(
                        $configuration->get('file'),
                        $minLevel ?: Logger::DEBUG
                    );
                    break;
                case static::HANDLER_FILE_ERROR:
                    $handlerInstance = new StreamHandler(
                        $configuration->get('file'),
                        $minLevel ?: Logger::WARNING
                    );
                    break;
                case static::HANDLER_STREAM:
                    $defaultLevelStream = !empty($levelOverride) ?
                        $this->normalizeLevel($levelOverride)
                        : Logger::INFO;
                    $handlerInstance = new StreamHandler(
                        $configuration->get('stream'),
                        $minLevel ?: $defaultLevelStream
                    );
                    break;
                case static::HANDLER_EMAIL:
                    $handlerInstance = new NativeMailerHandler(
                        $configuration->get('to'),
                        $configuration->get('subject', 'Log from Magento Cloud'),
                        $configuration->get('from'),
                        $minLevel ?: Logger::NOTICE
                    );
                    break;
                case static::HANDLER_SLACK:
                    $handlerInstance = new SlackHandler(
                        $configuration->get('token'),
                        $configuration->get('channel', 'general'),
                        $configuration->get('username', 'Slack Log Notifier'),
                        true,
                        null,
                        $minLevel ?: Logger::NOTICE
                    );
                    break;
                case static::HANDLER_SYSLOG:
                    $handlerInstance = new SyslogHandler(
                        $configuration->get('ident'),
                        $configuration->get('facility', LOG_USER),
                        $minLevel ?: Logger::NOTICE,
                        true,
                        $configuration->get('logopts', LOG_PID)
                    );
                    break;
                case static::HANDLER_SYSLOG_UDP:
                    $handlerInstance = new SyslogUdpHandler(
                        $configuration->get('host'),
                        $configuration->get('port'),
                        $configuration->get('facility', LOG_USER),
                        $minLevel ?: Logger::NOTICE,
                        true,
                        $configuration->get('ident', 'php')
                    );
                    break;
                case static::HANDLER_GELF:
                    $handlerInstance = $this->gelfHandlerFactory->create($configuration, $minLevel);
                    break;
                default:
                    throw new LoggerException('Unknown type of log handler: ' . $handler);
            }
        } catch (\Exception $exception) {
            throw new LoggerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $handlerInstance;
    }

    /**
     * @param string $level
     * @return int
     * @throws LoggerException
     */
    private function normalizeLevel(string $level): int
    {
        /** @phpstan-ignore-next-line */
        $normalizedLevel = Logger::toMonologLevel($level);

        if (!is_int($normalizedLevel)) {
            throw new LoggerException('Logger lever is incorrect');
        }

        return $normalizedLevel;
    }
}
