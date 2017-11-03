<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Handler;

use Monolog\Handler\SlackHandler;
use Magento\MagentoCloud\App\Logger\LevelResolver;

/**
 * The factory for SlackHandler
 */
class SlackFactory
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
     * @param array $configuration
     * @return SlackHandler
     */
    public function create(array $configuration): SlackHandler
    {
        return new SlackHandler(
            $configuration['token'],
            $configuration['chanel'] ?? 'general',
            $configuration['username'] ?? 'Slack Log Notifier',
            true,
            null,
            $this->levelResolver->resolve($configuration['min_level'])
        );
    }
}
