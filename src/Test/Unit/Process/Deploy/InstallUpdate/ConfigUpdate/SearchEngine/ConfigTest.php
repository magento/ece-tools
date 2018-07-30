<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ElasticSearch|Mock
     */
    private $elasticSearchMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->elasticSearchMock,
            $this->magentoVersionMock,
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
        $this->elasticSearchMock->expects($this->never())
            ->method('getVersion');

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
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn(['elasticsearch' => [$relationships]]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($version);

        $this->assertEquals($expected, $this->config->get());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                'customSearchConfig' => [
                    'elasticsearch_server_port' => 2345,
                    'elasticsearch_index_prefix' => 'new_prefix',
                    '_merge' => true,
                ],
                'version' => '2.4',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'localhost',
                    'elasticsearch_server_port' => 2345,
                    'elasticsearch_index_prefix' => 'new_prefix',
                ],
            ],
            [
                'customSearchConfig' => [
                    '_merge' => true,
                ],
                'version' => '2.4',
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'localhost',
                    'elasticsearch_server_port' => 1234,
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
