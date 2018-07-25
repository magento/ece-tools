<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ElasticSearchTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->elasticSearch = new ElasticSearch(
            $this->environmentMock,
            $this->clientFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetVersionElasticSearchNotExistInRelationShips()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertEquals(0, $this->elasticSearch->getVersion());
    }

    /**
     * @param array $relationships
     * @param string $esConfiguration
     * @param string $expectedVersion
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion(array $relationships, string $esConfiguration, string $expectedVersion)
    {
        $esConfig = $relationships['elasticsearch'][0];
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn($relationships);
        $clientMock->expects($this->once())
            ->method('get')
            ->with($esConfig['host'] . ':' . $esConfig['port'])
            ->willReturn($responseMock);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);
        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn($esConfiguration);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertSame($expectedVersion, $this->elasticSearch->getVersion());
    }

    /**
     * @return array
     */
    public function getVersionDataProvider(): array
    {
        $relationships = [
            'elasticsearch' => [
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                ],
            ],
        ];

        return [
            [
                $relationships,
                '{
                        "name" : "ZaIj9mo",
                        "cluster_name" : "elasticsearch",
                        "cluster_uuid" : "CIXBGIVdS6mwM_0lmVhF4g",
                        "version" : {
                            "number" : "5.1",
                            "build_hash" : "c59ff00"
                        },
                        "tagline" : "You Know, for Search"
                    }
                ',
                '5.1',
            ],
            [
                $relationships,
                '{"version" : {"number" : "0.1.5"}}',
                '0.1.5',
            ],
            [
                $relationships,
                '{"version" : {"number" : "1.0"}}',
                '1.0',
            ],
            [
                $relationships,
                '{"version" : {"number" : "2.4.4"}}',
                '2.4.4',
            ],
        ];
    }

    public function testGetVersionWithException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'elasticsearch' => [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                    ],
                ],
            ]);
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $clientMock->expects($this->once())
            ->method('get')
            ->with('127.0.0.1:1234')
            ->willThrowException(new \RuntimeException('ES is not available'));
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t get version of elasticsearch: ES is not available');

        $this->assertEquals(0, $this->elasticSearch->getVersion());
    }
}
