<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\Service\VarnishService;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class VarnishServiceTest extends TestCase
{
    /**
     * @var VarnishService
     */
    private $service;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->service = new VarnishService();
    }

    public function testGet()
    {
        $this->assertSame([
            'image' => 'magento/magento-cloud-docker-varnish:latest',
            'environment' => [
                'VIRTUAL_HOST' => 'magento2.docker',
                'VIRTUAL_PORT' => 80,
                'HTTPS_METHOD' => 'noredirect',
            ],
            'ports' => [
                '80:80',
            ],
            'links' => [
                'web',
            ],
        ], $this->service->get());
    }
}
