<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSuite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var ElasticSuite|MockObject
     */
    private $elasticSuiteMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->elasticSearchMock,
            $this->elasticSuiteMock,
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
        $generateDataForVersionChecking = function ($version, $engine) {
            return [
                'customSearchConfig' => [],
                'version' => $version,
                'relationships' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => $engine,
                    $engine . '_server_hostname' => 'localhost',
                    $engine . '_server_port' => 1234,
                ],
            ];
        };

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
            $generateDataForVersionChecking('1.7', 'elasticsearch'),
            $generateDataForVersionChecking('2.4', 'elasticsearch'),
            $generateDataForVersionChecking('5.0', 'elasticsearch5'),
            $generateDataForVersionChecking('5.2', 'elasticsearch5'),
            $generateDataForVersionChecking('6.0', 'elasticsearch6'),
            $generateDataForVersionChecking('6.2', 'elasticsearch6'),
            $generateDataForVersionChecking('7.2', 'elasticsearch7'),
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
