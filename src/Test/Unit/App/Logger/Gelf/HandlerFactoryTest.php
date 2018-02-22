<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger\Gelf;

use Gelf\Transport\HttpTransport;
use Gelf\Transport\TcpTransport;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Logger\Gelf\Handler;
use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory;
use Magento\MagentoCloud\App\Logger\Gelf\TransportFactory;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var TransportFactory|Mock
     */
    private $transportFactoryMock;

    /**
     * @var Repository|Mock
     */
    private $repositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->transportFactoryMock = $this->createMock(TransportFactory::class);
        $this->repositoryMock = $this->createMock(Repository::class);
        
        $this->handlerFactory = new HandlerFactory(
            $this->transportFactoryMock
        );
    }

    public function testCreate()
    {
        $httpTransportMock = $this->createMock(HttpTransport::class);
        $tcpTransportMock = $this->createMock(TcpTransport::class);
        
        $this->repositoryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['transport'],
                ['additional']
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'http' => [
                        'host' => 'localhost'
                    ],
                    'tcp' => [
                        'host' => '127.0.0.1'
                    ]
                ],
                [
                    'project' => 'some_project'
                ]
            );
        $this->transportFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['http', ['host' => 'localhost']],
                ['tcp', ['host' => '127.0.0.1']]
            )
            ->willReturnOnConsecutiveCalls(
                $httpTransportMock,
                $tcpTransportMock
            );

        $this->assertInstanceOf(
            Handler::class,
            $this->handlerFactory->create($this->repositoryMock, Logger::INFO)
        );
    }
}
