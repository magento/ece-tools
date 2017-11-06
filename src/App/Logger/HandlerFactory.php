<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\HandlerInterface;
use Magento\MagentoCloud\Config\Log as LogConfig;

/**
 * The handler factory
 */
class HandlerFactory
{
    const HANDLER_STREAM = 'stream';
    const HANDLER_FILE = 'file';
    const HANDLER_EMAIL = 'email';
    const HANDLER_SLACK = 'slack';

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
     * @param string $typeHandler
     * @return HandlerInterface
     * @throws \Exception
     */
    public function create(string $typeHandler): HandlerInterface
    {
        switch ($typeHandler) {
            case static::HANDLER_STREAM:
            case static::HANDLER_FILE:
                return new StreamHandler($this->logConfig->get($typeHandler)->get('stream'));
                break;
            case 'email':
                $configuration = $this->logConfig->get($typeHandler);
                return new NativeMailerHandler(
                    $configuration->get('to'),
                    $configuration->get('subject', 'Log form Magento Cloud'),
                    $configuration->get('from'),
                    $this->levelResolver->resolve($configuration->get('min_level'))
                );
                break;
            case 'slack':
                $configuration = $this->logConfig->get($typeHandler);
                return new SlackHandler(
                    $configuration->get('token'),
                    $configuration->get('chanel', 'general'),
                    $configuration->get('username', 'Slack Log Notifier'),
                    true,
                    null,
                    $this->levelResolver->resolve($configuration->get('min_level'))
                );
                break;
            default:
                throw new \Exception('Unknown type of log handler: ' . $typeHandler);
        }
    }
}
