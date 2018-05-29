<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ClientFactory|Mock
     */
    private $clientFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->clientFactoryMock,
            $this->magentoVersionMock,
            $this->loggerMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $envSearchConfig
     * @return void
     * @dataProvider getWhenCustomConfigValidWithoutMergeDataProvider
     */
    public function testGetWhenCustomConfigValidWithoutMerge(array $envSearchConfig)
    {
        $expectedConfig = ['engine' => 'some_engine'];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($envSearchConfig);

        $this->environmentMock->expects($this->never())
            ->method('getRelationships');
        $this->magentoVersionMock->expects($this->never())
            ->method('satisfies');
        $this->clientFactoryMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertEquals($expectedConfig, $this->config->get());
    }

    /**
     * @return array
     */
    public function getWhenCustomConfigValidWithoutMergeDataProvider(): array
    {
        return [
            [['engine' => 'some_engine']],
            [['engine' => 'some_engine', '_merge' => false]]
        ];
    }

    /**
     * @param string $version
     * @param array $relationships
     * @param array $expected
     * @param array $customSearchConfig
     * @dataProvider testGetWithElasticSearchDataProvider
     */
    public function testGetWithElasticSearch(
        array $customSearchConfig,
        string $version,
        array $relationships,
        array $expected
    ) {
        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock();
        $responseMock = $this->createMock(Response::class);
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(['elasticsearch' => [$relationships]]);
        $clientMock->expects($this->once())
            ->method('get')
            ->with($relationships['host'] . ':' . $relationships['port'])
            ->willReturn($responseMock);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);
        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn('{
                "name" : "ZaIj9mo",
                "cluster_name" : "elasticsearch",
                "cluster_uuid" : "CIXBGIVdS6mwM_0lmVhF4g",
                "version" : {
                    "number" : "' . $version . '",
                    "build_hash" : "c59ff00",
                    "build_date" : "2018-03-13T10:06:29.741383Z",
                    "build_snapshot" : false,
                    "lucene_version" : "7.2.1",
                    "minimum_wire_compatibility_version" : "5.6.0",
                    "minimum_index_compatibility_version" : "5.0.0"
                },
                "tagline" : "You Know, for Search"
            }
        ');
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        $this->assertEquals($expected, $this->config->get());
    }

    /**
     * @return array
     */
    public function testGetWithElasticSearchDataProvider(): array
    {
        return [
            [
                'customSearchConfig' => ['some_key' => 'some_value'],
                'version' => '2.4',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'query' => ['index' => 'stg'],
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'localhost',
                    'elasticsearch_server_port' => 1234,
                    'elasticsearch_index_prefix' => 'stg',
                ],
            ],
            [
                'customSearchConfig' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'some_host',
                    'elasticsearch_index_prefix' => 'prefix',
                    '_merge' => true,
                ],
                'version' => '2.4',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'some_host',
                    'elasticsearch_server_port' => 1234,
                    'elasticsearch_index_prefix' => 'prefix',
                ],
            ],
            [
                'customSearchConfig' => [],
                'version' => '5',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'query' => ['index' => 'stg'],
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'localhost',
                    'elasticsearch5_server_port' => 1234,
                    'elasticsearch5_index_prefix' => 'stg',
                ],
            ],
            [
                'customSearchConfig' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'some_host',
                    'elasticsearch5_index_prefix' => 'prefix',
                    '_merge' => true,
                ],
                'version' => '5.1',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'some_host',
                    'elasticsearch5_server_port' => 1234,
                    'elasticsearch5_index_prefix' => 'prefix',
                ],
            ],
            [
                'customSearchConfig' => [],
                'version' => '6.2',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'localhost',
                    'elasticsearch5_server_port' => 1234,
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetWithElasticSearchException()
    {
        $relationships = ['host' => 'localhost', 'port' => 1234];
        $expected = [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => 'localhost',
            'elasticsearch_server_port' => 1234,
        ];
        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('get')
            ->with($relationships['host'] . ':' . $relationships['port'])
            ->willThrowException(new \RuntimeException('ES is not available'));
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(['elasticsearch' => [$relationships]]);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('ES is not available');

        $this->assertEquals($expected, $this->config->get());
    }

    /**]@return void
     */
    public function testGetWithSolr()
    {
        $expectsConfig = [
            'engine' => 'solr',
            'solr_server_hostname' => 'localhost',
            'solr_server_port' => 1234,
            'solr_server_username' => 'scheme',
            'solr_server_path' => 'path',
        ];

        $this->magentoVersionMock->method('satisfies')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(
                [
                    'solr' => [
                        [
                            'host' => 'localhost',
                            'port' => 1234,
                            'scheme' => 'scheme',
                            'path' => 'path',
                        ],
                    ],
                ]
            );
        $this->assertEquals($expectsConfig, $this->config->get());
    }
}
