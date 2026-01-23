<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Gelf;

use Gelf\Transport\HttpTransport;
use Gelf\Transport\TcpTransport;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory;
use Magento\MagentoCloud\App\Logger\Gelf\TransportFactory;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class HandlerFactoryTest extends TestCase
{
    /**
     * @var HandlerFactory;
     */
    private $handlerFactory;

    /**
     * @var TransportFactory|MockObject
     */
    private $transportFactoryMock;

    /**
     * @var Repository|MockObject
     */
    private $repositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->transportFactoryMock = $this->createMock(TransportFactory::class);
        $this->repositoryMock = $this->createMock(Repository::class);

        $this->handlerFactory = new HandlerFactory(
            $this->transportFactoryMock
        );
    }

    /**
     * @throws \Exception
     */
    public function testCreate(): void
    {
        $httpTransportMock = $this->createStub(HttpTransport::class);
        $tcpTransportMock = $this->createStub(TcpTransport::class);

        $this->repositoryMock->expects($this->exactly(2))
            ->method('get')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['transport'] => [
                    'http' => [
                        'host' => 'localhost'
                    ],
                    'tcp' => [
                        'host' => '127.0.0.1'
                    ]
                ],
                ['additional'] => [
                    'project' => 'some_project'
                ]
            });
        $series = [
            [['http', ['host' => 'localhost']], $httpTransportMock],
            [['tcp', ['host' => '127.0.0.1']], $tcpTransportMock],
        ];
        $this->transportFactoryMock->expects($this->exactly(2))
            ->method('create')
            // withConsecutive() alternative.
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);
                return $return;
            });

        $this->handlerFactory->create($this->repositoryMock, Logger::INFO);
    }
}
