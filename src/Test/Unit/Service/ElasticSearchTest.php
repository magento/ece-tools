<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\SearchEngine;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Service\ElasticSearch;
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
            ->method('getRelationship')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertEquals(0, $this->elasticSearch->getVersion());
    }

    /**
     * @param array $esRelationship
     * @param string $esConfiguration
     * @param string $expectedVersion
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion(array $esRelationship, string $esConfiguration, string $expectedVersion)
    {
        $esConfig = $esRelationship[0];
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->willReturn($esRelationship);
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
            [
                'host' => '127.0.0.1',
                'port' => '1234',
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
            ->method('getRelationship')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
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

    public function testGetTemplate()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                ]
            ]);
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $esConfiguration = json_encode(
            [
                'default' => [
                    'settings' => [
                        'index' => [
                            'number_of_shards' => 1,
                            'number_of_replicas' => 2
                        ]
                    ]
                ]
            ]
        );
        $clientMock->expects($this->once())
            ->method('get')
            ->with('127.0.0.1:1234/_template')
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

        $this->assertSame(
            [
                'index' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 2,
                ]
            ],
            $this->elasticSearch->getTemplate()
        );
    }

    public function testGetTemplateNoConfig()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([]);

        $this->assertSame([], $this->elasticSearch->getTemplate());
    }

    public function testGetTemplateWithException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                    ],
                ]
            );
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);

        $clientMock->expects($this->once())
            ->method('get')
            ->with('127.0.0.1:1234/_template')
            ->willThrowException(new \Exception('Some error'));
        $clientMock->expects($this->once())
            ->method('get')
            ->with('127.0.0.1:1234/_template')
            ->willReturn($responseMock);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t get configuration of elasticsearch: Some error');

        $this->assertSame([], $this->elasticSearch->getTemplate());
    }

    public function testIsInstalled()
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                    ],
                ],
                []
            );

        $this->assertTrue($this->elasticSearch->isInstalled());
        $this->assertFalse($this->elasticSearch->isInstalled());
    }
}
