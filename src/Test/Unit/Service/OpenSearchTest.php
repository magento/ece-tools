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
use Magento\MagentoCloud\Service\OpenSearch;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @see OpenSearch
 */
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
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->openSearch = new OpenSearch(
            $this->environmentMock,
            $this->clientFactoryMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
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
     * @param array $osRelationship
     * @param string $osConfiguration
     * @param string $expectedVersion
     * @throws ServiceException
     *
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion(array $osRelationship, string $osConfiguration, string $expectedVersion): void
    {
        $esConfig = $osRelationship[0];
        $clientMock = $this->createPartialMock(Client::class, ['get']);
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

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
     * @return array
     */
    public function getVersionDataProvider(): array
    {
        $relationships = [
            [
                'host' => '127.0.0.1',
                'port' => '1234',
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
        ];
    }

    /**
     * @param array $osRelationship
     * @param string $expectedVersion
     * @throws ServiceException
     *
     * @dataProvider getVersionFromTypeDataProvider
     */
    public function testGetVersionFromType($osRelationship, $expectedVersion)
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturn($osRelationship);

        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->assertSame($expectedVersion, $this->openSearch->getVersion());
    }

    public function getVersionFromTypeDataProvider()
    {
        return [
            [
                [],
                '0'
            ],
            [
                [
                    ['host' => '127.0.0.1', 'port' => '1234', 'type' => 'opensearch:1.0']
                ],
                '1.0'
            ],
            [
                [
                    ['host' => '127.0.0.1', 'port' => '1234', 'type' => 'opensearch:1.1']
                ],
                '1.1'
            ],

        ];
    }

    /**
     * @param bool $greaterOrEqual
     * @param string $expectedResult
     * @throws ServiceException
     * @dataProvider getFullEngineNameDataProvider
     */
    public function testGetFullEngineName(bool $greaterOrEqual, string $expectedResult): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn($greaterOrEqual);
        $this->assertSame($expectedResult, $this->openSearch->getFullEngineName());
    }

    /**
     * @return array
     */
    public function getFullEngineNameDataProvider()
    {
        return [
            [false, 'elasticsearch7'],
            [true, 'opensearch'],
        ];
    }

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
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $osConfiguration = json_encode(
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
            ->willReturn($osConfiguration);
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
            $this->openSearch->getTemplate()
        );
    }

    public function testGetTemplateNoConfig(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('opensearch')
            ->willReturn([]);

        $this->assertSame([], $this->openSearch->getTemplate());
    }

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

    public function testAuthEnabledTrue()
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->with('opensearch')
            ->willReturn(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '1234',
                        'username' => 'test',
                        'password' => 'secret',
                    ],
                ]
            );

        $this->assertTrue($this->openSearch->isAuthEnabled());
    }

    public function testAuthEnabledFalse()
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
