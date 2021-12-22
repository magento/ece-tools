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
use Magento\MagentoCloud\Service\OpenSearch;
use Magento\MagentoCloud\Service\ServiceException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see SearchEngine
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
     * @var OpenSearch|MockObject
     */
    private $openSearchMock;

    /**
     * @var ElasticSuite|MockObject
     */
    private $elasticSuiteMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->openSearchMock = $this->createMock(OpenSearch::class);
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);

        $this->config = new SearchEngine(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->elasticSearchMock,
            $this->openSearchMock,
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
    public function testGetWhenCustomConfigValidWithoutMerge(array $envSearchConfig): void
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
        $this->elasticSearchMock->expects($this->never())
            ->method('getFullEngineName')
            ->willReturn('elasticsearch');

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
     * @param array $esServiceConfig
     * @param array $expected
     * @param bool $authEnabled
     *
     * @throws ServiceException
     * @dataProvider getWithElasticSearchDataProvider
     */
    public function testGetWithElasticSearch(
        array $customSearchConfig,
        array $esServiceConfig,
        array $expected,
        bool $authEnabled = false
    ): void {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->elasticSearchMock->expects($this->exactly(2))
            ->method('getConfiguration')
            ->willReturn($esServiceConfig);
        $this->openSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->elasticSearchMock->expects($this->once())
            ->method('isAuthEnabled')
            ->willReturn($authEnabled);
        $this->openSearchMock->expects($this->never())
            ->method('isAuthEnabled');
        $this->elasticSearchMock->expects($this->once())
            ->method('getFullEngineName')
            ->willReturn('elasticsearch');
        $this->openSearchMock->expects($this->never())
            ->method('getFullEngineName');
        $this->elasticSearchMock->method('getHost')
            ->willReturn($esServiceConfig['host']);

        $expected = ['system' => ['default' => ['catalog' => ['search' => $expected]]]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getWithElasticSearchDataProvider(): array
    {
        $generateDataForVersionChecking = static function ($engine) {
            return [
                'customSearchConfig' => [],
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
                'esServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'password' => 'secret',
                    'username' => 'user',
                ],
                'expected' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'localhost',
                    'elasticsearch_server_port' => 1234,
                    'elasticsearch_enable_auth' => 1,
                    'elasticsearch_username' => 'user',
                    'elasticsearch_password' => 'secret',
                ],
                true
            ],
            $generateDataForVersionChecking('elasticsearch'),
            $generateDataForVersionChecking('elasticsearch'),
        ];
    }

    /**
     * @param array $customSearchConfig
     * @param array $osServiceConfig
     * @param array $expected
     * @param bool $authEnabled
     *
     * @throws ServiceException
     * @dataProvider getWithOpenSearchDataProvider
     */
    public function testGetWithOpenSearch(
        array $customSearchConfig,
        array $osServiceConfig,
        array $expected,
        bool $authEnabled = false
    ): void {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->elasticSearchMock->expects($this->never())
            ->method('getConfiguration');
        $this->elasticSearchMock->expects($this->never())
            ->method('isAuthEnabled');
        $this->elasticSearchMock->expects($this->never())
            ->method('getFullEngineName');
        $this->openSearchMock->expects($this->exactly(2))
            ->method('getConfiguration')
            ->willReturn($osServiceConfig);
        $this->openSearchMock->expects($this->once())
            ->method('isAuthEnabled')
            ->willReturn($authEnabled);
        $this->openSearchMock->expects($this->once())
            ->method('getFullEngineName')
            ->willReturn('opensearch');
        $this->openSearchMock->method('getHost')
            ->willReturn($osServiceConfig['host']);

        $expected = ['system' => ['default' => ['catalog' => ['search' => $expected]]]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getWithOpenSearchDataProvider(): array
    {
        $generateDataForVersionChecking = static function ($engine) {
            return [
                'customSearchConfig' => [],
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
                'osServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'query' => ['index' => 'stg'],
                ],
                'expected' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'localhost',
                    'opensearch_server_port' => 1234,
                    'opensearch_index_prefix' => 'stg',
                ],
            ],
            [
                'customSearchConfig' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'some_host',
                    'opensearch_index_prefix' => 'prefix',
                    '_merge' => true,
                ],
                'osServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'some_host',
                    'opensearch_server_port' => 1234,
                    'opensearch_index_prefix' => 'prefix',
                ],
            ],
            [
                'customSearchConfig' => [
                    'opensearch_server_port' => 2345,
                    'opensearch_index_prefix' => 'new_prefix',
                    '_merge' => true,
                ],
                'osServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'localhost',
                    'opensearch_server_port' => 2345,
                    'opensearch_index_prefix' => 'new_prefix',
                ],
            ],
            [
                'customSearchConfig' => [
                    '_merge' => true,
                ],
                'osServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                ],
                'expected' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'localhost',
                    'opensearch_server_port' => 1234,
                ],
            ],
            [
                'customSearchConfig' => [],
                'osServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 1234,
                    'password' => 'secret',
                    'username' => 'user',
                ],
                'expected' => [
                    'engine' => 'opensearch',
                    'opensearch_server_hostname' => 'localhost',
                    'opensearch_server_port' => 1234,
                    'opensearch_enable_auth' => 1,
                    'opensearch_username' => 'user',
                    'opensearch_password' => 'secret',
                ],
                true
            ],
            $generateDataForVersionChecking('opensearch'),
            $generateDataForVersionChecking('opensearch'),
        ];
    }

    /**
     * @param array $customSearchConfig
     * @param array $esServiceConfig
     * @param array $expected
     *
     * @dataProvider getWithElasticSuiteDataProvider
     */
    public function testGetWithElasticSuite(
        array $customSearchConfig,
        array $esServiceConfig,
        array $expected
    ): void {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SEARCH_CONFIGURATION)
            ->willReturn($customSearchConfig);
        $this->elasticSearchMock->expects($this->exactly(2))
            ->method('getConfiguration')
            ->willReturn($esServiceConfig);
        $this->openSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);
        $this->elasticSuiteMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSuiteMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'servers' => 'localhost'
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getFullEngineName')
            ->willReturn('elasticsearch');
        $this->elasticSearchMock->method('getHost')
            ->willReturn($esServiceConfig['host']);

        $expected = ['system' => ['default' => $expected]];

        $this->assertEquals($expected, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getWithElasticSuiteDataProvider(): array
    {
        return [
            [
                'customSearchConfig' => ['some_key' => 'some_value'],
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
    public function testGetWithSolr(): void
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

    public function testGetName(): void
    {
        $this->assertSame('mysql', $this->config->getName());
    }

    /**
     * @param $searchConfig
     * @param bool $expected
     *
     * @dataProvider isEsFamilyDataProvider
     */
    public function testIsEsFamily(array $searchConfig, bool $expected): void
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
            [['engine' => 'elasticsearch7'], true],
            [['engine' => 'opensearch'], true],
            [['engine' => 'elasticsuite'], true],
            [['engine' => 'some'], false],
        ];
    }
}
