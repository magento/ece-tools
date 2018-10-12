<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Service;

/**
 * @inheritdoc
 */
class RabbitMqService implements ServiceInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return ['image' => 'rabbitmq:latest'];
    }
}
