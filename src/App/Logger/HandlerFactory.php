<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\Logger;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\HandlerInterface;

/**
 * The handler factory
 */
class HandlerFactory
{
    /**
     * @var LevelResolver
     */
    private $levelResolver;

    /**
     * @param LevelResolver $levelResolver
     */
    public function __construct(LevelResolver $levelResolver)
    {
        $this->levelResolver = $levelResolver;
    }

    /**
     * @param string $type
     * @param array $configuration
     * @return HandlerInterface
     * @throws \Exception
     */
    public function create(string $type, array $configuration = []): HandlerInterface
    {
        switch ($type) {
            case 'stream':
                return new StreamHandler($configuration['stream'] ?? 'php://stdout');
                break;
            case 'email':
                return new NativeMailerHandler(
                    $configuration['to'],
                    $configuration['subject'] ?? 'Log form Magento Cloud',
                    $configuration['from'],
                    $this->levelResolver->resolve($configuration['min_level'])
                );
                break;
            case 'slack':
                return new SlackHandler(
                    $configuration['token'],
                    $configuration['chanel'] ?? 'general',
                    $configuration['username'] ?? 'Slack Log Notifier',
                    true,
                    null,
                    $this->levelResolver->resolve($configuration['min_level'])
                );
                break;
            default:
                throw new \Exception(
                    'Unknown type of log handler: ' . $type . ' in ' . Logger::CONFIG_HANDLERS_LOG . ' file'
                );
        }
    }
}
