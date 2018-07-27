<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Service;

/**
 * @inheritdoc
 */
class RedisService implements ServiceInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'image' => 'magento/magento-cloud-docker-redis:latest',
            'volumes' => [
                '/data',
            ],
            'ports' => [
                6379,
            ],
        ];
    }
}
