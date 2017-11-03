<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Handler;

use Monolog\Handler\NativeMailerHandler;
use Magento\MagentoCloud\App\Logger\LevelResolver;

/**
 * The factory for NativeMailerHandler
 */
class EmailFactory
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
     * @return NativeMailerHandler
     */
    public function create(array $configuration): NativeMailerHandler
    {
        return new NativeMailerHandler(
            $configuration['to'],
            $configuration['subject'] ?? 'Log form Magento Cloud',
            $configuration['from'],
            $this->levelResolver->resolve($configuration['min_level'])
        );
    }
}
