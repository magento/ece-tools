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
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\OpenSearch;
use Magento\MagentoCloud\Service\ServiceException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @see OpenSearch
 */
#[AllowMockObjectsWithoutExpectations]
class OpenSearchTest extends TestCase
{
    /**
     * @var OpenSearch
     */
    private $openSearch;

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
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock    = $this->createMock(Environment::class);
        $this->loggerMock         = $this->createMock(LoggerInterface::class);
        $this->clientFactoryMock  = $this->createMock(ClientFactory::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->openSearch = new OpenSearch(
            $this->environmentMock,
            $this->clientFactoryMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
     * Test get version elasticsearch not exist in relationships.
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

        $this->assertEquals('0', $this->openSearch->getVersion());
    }

    /**
     * Test get version method.
     *
     * @param array $osRelationship
     * @param string $osConfiguration
     * @param string $expectedVersion
     * @dataProvider getVersionDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionDataProvider')]
    public function testGetVersion(array $osRelationship, string $osConfiguration, string $expectedVersion): void
    {
        $esConfig = $osRelationship[0];
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturn($osRelationship);
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
            ->willReturn($osConfiguration);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertSame($expectedVersion, $this->openSearch->getVersion());
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
                        "cluster_name" : "opensearch",
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
            [
                $relationships,
                '{"version" : {"number" : "3.0.0"}}',
                '3.0.0',
            ],
        ];
    }

    /**
     * @param array $osRelationship
     * @param string $expectedVersion
     * @dataProvider getVersionFromTypeDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionFromTypeDataProvider')]
    public function testGetVersionFromType(array $osRelationship, string $expectedVersion): void
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturn($osRelationship);

        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertSame($expectedVersion, $this->openSearch->getVersion());
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
                        'type' => 'opensearch:1.0',
                    ]
                ],
                '1.0'
            ],
            [
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'type' => 'opensearch:1.1',
                    ]
                ],
                '1.1'
            ],
            [
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'type' => 'opensearch:2.0',
                    ]
                ],
                '2.0'
            ],
            [
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'type' => 'opensearch:3.0',
                    ]
                ],
                '3.0'
            ],
        ];
    }

    /**
     * Test get full engine name method.
     *
     * @param bool $greaterOrEqual
     * @param string $expectedResult
     * @dataProvider getFullEngineNameDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getFullEngineNameDataProvider')]
    public function testGetFullEngineName(bool $greaterOrEqual, string $expectedResult): void
    {
        $this->magentoVersionMock->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn($greaterOrEqual);
        $this->assertSame($expectedResult, $this->openSearch->getFullEngineName());
    }

    /**
     * Data provider for get full engine name method.
     *
     * @return array
     */
    public static function getFullEngineNameDataProvider(): array
    {
        return [
            [
                true,
                'elasticsearch7'
            ],
            [
                false,
                'elasticsearch7'
            ],
        ];
    }

    /**
     * Test get version with exception.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionWithException(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Can\'t get version of opensearch: OS is not available');
        $this->expectExceptionCode(Error::DEPLOY_OS_CANNOT_CONNECT);

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
            ->willThrowException(new \RuntimeException('OS is not available'));
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        $this->assertEquals(0, $this->openSearch->getVersion());
    }

    /**
     * Test get template.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetTemplate(): void
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with('opensearch')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                ]
            ]);
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $osConfiguration = json_encode(
            [
                'default' => [
                    'settings' => [
                        'index' => [
                            'number_of_shards'   => 1,
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
            ->willReturn($osConfiguration);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertSame(
            [
                'index' => [
                    'number_of_shards'   => 1,
                    'number_of_replicas' => 2,
                ]
            ],
            $this->openSearch->getTemplate()
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
            ->with('opensearch')
            ->willReturn([]);

        $this->assertSame([], $this->openSearch->getTemplate());
    }

    /**
     * Test get template no config.
     *
     * @return void
     */
    public function testGetTemplateWithException(): void
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with('opensearch')
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
            ->with('Can\'t get configuration of opensearch: Some error');

        $this->assertSame([], $this->openSearch->getTemplate());
    }

    /**
     * Test is installed.
     *
     * @return void
     * @throws ServiceException
     */
    public function testIsInstalled(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->with('opensearch')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                    ],
                ],
                []
            );

        $this->assertTrue($this->openSearch->isInstalled());
        $this->assertFalse($this->openSearch->isInstalled());
    }

    /**
     * Test auth enabled true.
     *
     * @return void
     * @throws ServiceException
     */
    public function testAuthEnabledTrue(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->with('opensearch')
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

        $this->assertTrue($this->openSearch->isAuthEnabled());
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
            ->with('opensearch')
            ->willReturn(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'password' => '',
                    ],
                ]
            );

        $this->assertFalse($this->openSearch->isAuthEnabled());
    }
}
