<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger\Gelf;

use Gelf\Transport\HttpTransport;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\UdpTransport;
use Magento\MagentoCloud\App\Logger\Gelf\TransportFactory;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class TransportFactoryTest extends TestCase
{
    /**
     * @var TransportFactory
     */
    private $transportFactory;

    protected function setUp()
    {
        $this->transportFactory = new TransportFactory();
    }

    /**
     * @param string $type
     * @param string $expectedClass
     * @dataProvider createDataProvider
     */
    public function testCreate(string $type, string $expectedClass)
    {
        $transport = $this->transportFactory->create($type, [
            'host' => 'localhost',
            'port' => 3306
        ]);

        $this->assertInstanceOf(
            $expectedClass,
            $transport
        );
    }

    public function createDataProvider()
    {
        return [
            [
                TransportFactory::TRANSPORT_TCP,
                TcpTransport::class
            ],
            [
                TransportFactory::TRANSPORT_HTTP,
                HttpTransport::class
            ],
            [
                TransportFactory::TRANSPORT_UDP,
                UdpTransport::class
            ]
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown transport type:
     */
    public function testCreateUnknownType()
    {
        $this->transportFactory->create('unknown_type', []);
    }
}
