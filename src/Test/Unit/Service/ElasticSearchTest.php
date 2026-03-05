<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\ServiceException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @see ElasticSearch
 */
#[AllowMockObjectsWithoutExpectations]
class ElasticSearchTest extends TestCase
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

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
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->elasticSearch = new ElasticSearch(
            $this->environmentMock,
            $this->clientFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test get version elasticsearch not exist in relationships method.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionElasticSearchNotExistInRelationships(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->willReturn([]);
        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertEquals('0', $this->elasticSearch->getVersion());
    }

    /**
     * Test get version method.
     *
     * @param array $esRelationship
     * @param string $esConfiguration
     * @param string $expectedVersion
     * @dataProvider getVersionDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionDataProvider')]
    public function testGetVersion(array $esRelationship, string $esConfiguration, string $expectedVersion): void
    {
        $esConfig = $esRelationship[0];
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturn($esRelationship);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $clientMock->expects($this->once())
            ->method('get')
            ->with($esConfig['host'] . ':' . $esConfig['port'], ['auth' => ['user', 'secret']])
            ->willReturn($responseMock);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);
        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn($esConfiguration);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertSame($expectedVersion, $this->elasticSearch->getVersion());
    }

    /**
     * Data provider for get version method.
     *
     * @return array
     */
    public static function getVersionDataProvider(): array
    {
        $relationships = [
            [
                'host'     => '127.0.0.1',
                'port'     => '1234',
                'username' => 'user',
                'password' => 'secret'
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

    /**
     * Test get version from type method.
     *
     * @param array $esRelationship
     * @param string $expectedVersion
     * @dataProvider getVersionFromTypeDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionFromTypeDataProvider')]
    public function testGetVersionFromType($esRelationship, $expectedVersion)
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturn($esRelationship);

        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertSame($expectedVersion, $this->elasticSearch->getVersion());
    }

    /**
     * Data provider for get version from type method.
     *
     * @return array
     */
    public static function getVersionFromTypeDataProvider(): array
    {
        return [
            [
                [],
                '0'
            ],
            [
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'type' => 'elasticsearch:7.7'
                    ]
                ],
                '7.7'
            ],
            [
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'type' => 'elasticsearch:5.2'
                    ]
                ],
                '5.2'
            ],
        ];
    }

    /**
     * Test get full engine name method.
     *
     * @param string $version
     * @param string $expected
     * @dataProvider getFullVersionDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getFullVersionDataProvider')]
    public function testGetFullEngineName(string $version, string $expected): void
    {
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $esConfig = [
            'host' => '127.0.0.1',
            'port' => '1234',
        ];
        $esRelationship = [$esConfig];
        $esConfiguration = json_encode([
            'name'         => 'ZaIj9mo',
            'cluster_name' => 'elasticsearch',
            'cluster_uuid' => 'CIXBGIVdS6mwM_0lmVhF4g',
            'version'      => [
                'number'     => $version,
                'build_hash' => 'c59ff00'
            ],
            'tagline' => 'You Know, for Search'
        ]);

        $this->environmentMock->method('getRelationship')
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

        $this->assertSame($expected, $this->elasticSearch->getFullEngineName());
    }

    /**
     * Data provider for get full engine name method.
     *
     * @return array
     */
    public static function getFullVersionDataProvider(): array
    {
        return [
            [
                '5.1',
                'elasticsearch5'
            ],
            [
                '2.4',
                'elasticsearch'
            ],
            [
                '7.7',
                'elasticsearch7'
            ]
        ];
    }

    /**
     * Test get version with exception method.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionWithException(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Can\'t get version of elasticsearch: ES is not available');
        $this->expectExceptionCode(Error::DEPLOY_ES_CANNOT_CONNECT);

        $this->environmentMock->method('getRelationship')
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

        $this->assertEquals(0, $this->elasticSearch->getVersion());
    }

    /**
     * Test get template method.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetTemplate(): void
    {
        $this->environmentMock->expects($this->any())
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
        $streamMock = $this->createMock(StreamInterface::class);

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
            ->with('127.0.0.1:1234/_template/platformsh_index_settings')
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

    /**
     * Test get template no config.
     *
     * @return void
     */
    public function testGetTemplateNoConfig(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([]);

        $this->assertSame([], $this->elasticSearch->getTemplate());
    }

    /**
     * Test get template with exception.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetTemplateWithException(): void
    {
        $this->environmentMock->expects($this->any())
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

        $clientMock->expects($this->once())
            ->method('get')
            ->with('127.0.0.1:1234/_template/platformsh_index_settings')
            ->willThrowException(new \Exception('Some error'));
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t get configuration of elasticsearch: Some error');

        $this->assertSame([], $this->elasticSearch->getTemplate());
    }

    /**
     * Test is installed method.
     *
     * @return void
     * @throws ServiceException
     */
    public function testIsInstalled(): void
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

    /**
     * Test auth enabled true.
     *
     * @return void
     * @throws ServiceException
     */
    public function testAuthEnabledTrue()
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn(
                [
                    [
                        'host'     => '127.0.0.1',
                        'port'     => '1234',
                        'username' => 'test',
                        'password' => 'secret',
                    ],
                ]
            );

        $this->assertTrue($this->elasticSearch->isAuthEnabled());
    }

    /**
     * Test auth enabled false.
     *
     * @return void
     * @throws ServiceException
     */
    public function testAuthEnabledFalse(): void
    {
        $this->environmentMock->expects($this->exactly(1))
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'password' => '',
                    ],
                ]
            );

        $this->assertFalse($this->elasticSearch->isAuthEnabled());
    }
}
