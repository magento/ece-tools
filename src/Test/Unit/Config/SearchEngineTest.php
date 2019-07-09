<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ElasticSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SearchEngineTest extends TestCase
{
    /**
     * @var SearchEngine
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

        $this->config = new SearchEngine(
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
        $expectedConfig = ['system' => ['default' => ['catalog' => ['search' => ['engine' => 'some_engine']]]]];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($envSearchConfig);
        $this->magentoVersionMock->expects($this->never())
            ->method('satisfies');
        $this->elasticSearchMock->expects($this->never())
            ->method('getVersion');

        $this->assertEquals($expectedConfig, $this->config->getConfig());
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
     * @param array $customSearchConfig
     * @param string $version
     * @param array $esServiceConfig
     * @param array $expected
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     * @dataProvider testGetWithElasticSearchDataProvider
     */
    public function testGetWithElasticSearch(
        array $customSearchConfig,
        string $version,
        array $esServiceConfig,
        array $expected
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($esServiceConfig);
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($version);

        $expected = ['system' => ['default' => ['catalog' => ['search' => $expected]]]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetWithElasticSearchDataProvider(): array
    {
        $generateDataForVersionChecking = static function ($version, $engine) {
            return [
                'customSearchConfig' => [],
                'version' => $version,
                'relationship' => [
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
                'esServiceConfig' => [
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
                'esServiceConfig' => [
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
                'esServiceConfig' => [
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
                'esServiceConfig' => [
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
                'esServiceConfig' => [
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
                'esServiceConfig' => [
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
     * @param array $customSearchConfig
     * @param string $version
     * @param array $esServiceConfig
     * @param array $expected
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     * @dataProvider testGetWithElasticSuiteDataProvider
     */
    public function testGetWithElasticSuite(
        array $customSearchConfig,
        string $version,
        array $esServiceConfig,
        array $expected
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($esServiceConfig);
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($version);
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSuiteMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'servers' => 'localhost'
            ]);

        $expected = ['system' => ['default' => $expected]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetWithElasticSuiteDataProvider(): array
    {
        return [
            [
                'customSearchConfig' => ['some_key' => 'some_value'],
                'version' => '2.4',
                'esServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'query' => ['index' => 'stg'],
                ],
                'expected' => [
                    'catalog' => [
                        'search' => [
                            'engine' => 'elasticsuite',
                            'elasticsearch_server_hostname' => 'localhost',
                            'elasticsearch_server_port' => 1234,
                            'elasticsearch_index_prefix' => 'stg',
                        ]
                    ],
                    'smile_elasticsuite_core_base_settings' => [
                        'servers' => 'localhost'
                    ]
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetWithSolr()
    {
        $expected = [
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
            ->method('getRelationship')
            ->with('solr')
            ->willReturn([
                [
                    'host' => 'localhost',
                    'port' => 1234,
                    'scheme' => 'scheme',
                    'path' => 'path',
                ],
            ]);

        $expected = ['system' => ['default' => ['catalog' => ['search' => $expected]]]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    public function testGetName()
    {
        $this->assertSame('mysql', $this->config->getName());
    }

    /**
     * @param $searchConfig
     * @param bool $expected
     *
     * @dataProvider isEsFamilyDataProvider
     */
    public function testIsEsFamily(array $searchConfig, bool $expected)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($searchConfig);

        $this->assertSame($expected, $this->config->isESFamily());
    }

    public function isEsFamilyDataProvider(): array
    {
        return [
            [[], false],
            [['engine' => 'elasticsearch'], true],
            [['engine' => 'elasticsearch5'], true],
            [['engine' => 'elasticsuite'], true],
            [['engine' => 'some'], false],
        ];
    }
}
