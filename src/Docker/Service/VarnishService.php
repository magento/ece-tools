<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Service;

/**
 * @inheritdoc
 */
class VarnishService implements ServiceInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'image' => 'magento/magento-cloud-docker-varnish:latest',
            'environment' => [
                'VIRTUAL_HOST' => 'magento2.docker',
                'VIRTUAL_PORT' => 80,
                'HTTPS_METHOD' => 'noredirect',
            ],
            'ports' => [
                80,
            ],
            'links' => [
                'web',
            ],
        ];
    }
}
