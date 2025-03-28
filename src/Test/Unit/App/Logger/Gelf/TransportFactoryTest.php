<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->transportFactory = new TransportFactory();
    }

    /**
     * @param string $type
     * @param string $expectedClass
     * @dataProvider createDataProvider
     * @throws \Exception
     */
    public function testCreate(string $type, string $expectedClass)
    {
        $transport = $this->transportFactory->create($type, [
            'host' => 'localhost',
            'port' => 3306,
            'connection_timeout' => 10
        ]);

        $this->assertInstanceOf(
            $expectedClass,
            $transport
        );
    }

    public function createDataProvider(): array
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

    public function testCreateUnknownType()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown transport type:');

        $this->transportFactory->create('unknown_type', []);
    }
}
