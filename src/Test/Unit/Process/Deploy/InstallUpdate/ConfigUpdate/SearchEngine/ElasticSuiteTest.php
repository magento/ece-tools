<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSuite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ElasticSuiteTest extends TestCase
{
    /**
     * @var ElasticSuite
     */
    private $model;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var ConfigMerger|MockObject
     */
    private $configMergerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->configMergerMock = $this->createMock(ConfigMerger::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->model = new ElasticSuite(
            $this->managerMock,
            $this->stageConfigMock,
            $this->configMergerMock,
            $this->environmentMock,
            $this->elasticSearchMock
        );
    }

    public function testGetNoES()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION)
            ->willReturn(['some' => 'value']);
        $this->configMergerMock->expects($this->once())
            ->method('mergeConfigs')
            ->with([], ['some' => 'value'])
            ->willReturn(['some' => 'value']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([]);

        $this->assertSame(
            ['some' => 'value'],
            $this->model->get()
        );
    }

    public function testGet()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION)
            ->willReturn(['some' => 'value']);
        $this->configMergerMock->expects($this->once())
            ->method('mergeConfigs')
            ->with(
                [
                    'es_client' => [
                        'servers' => '127.0.0.1:1234',
                        'indices_alias' => 'magento2'
                    ],
                    'indices_settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 2
                    ]
                ],
                ['some' => 'value']
            )
            ->willReturn(['some' => 'value']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                    'query' => [
                        'index' => 'magento2'
                    ]
                ],
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getTemplate')
            ->willReturn([
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '2',
                ]
            ]);

        $this->assertSame(
            ['some' => 'value'],
            $this->model->get()
        );
    }

    public function testGetOnlyReplica()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION)
            ->willReturn(['some' => 'value']);
        $this->configMergerMock->expects($this->once())
            ->method('mergeConfigs')
            ->with(
                [
                    'es_client' => [
                        'servers' => '127.0.0.1:1234',
                        'indices_alias' => 'magento2'
                    ],
                    'indices_settings' => [
                        'number_of_replicas' => 2
                    ]
                ],
                ['some' => 'value']
            )
            ->willReturn(['some' => 'value']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                    'query' => [
                        'index' => 'magento2'
                    ]
                ],
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getTemplate')
            ->willReturn([
                'index' => [
                    'number_of_replicas' => '2',
                ]
            ]);

        $this->assertSame(
            ['some' => 'value'],
            $this->model->get()
        );
    }

    public function testGetOnlyShards()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION)
            ->willReturn(['some' => 'value']);
        $this->configMergerMock->expects($this->once())
            ->method('mergeConfigs')
            ->with(
                [
                    'es_client' => [
                        'servers' => '127.0.0.1:1234',
                        'indices_alias' => 'magento2'
                    ],
                    'indices_settings' => [
                        'number_of_shards' => 1
                    ]
                ],
                ['some' => 'value']
            )
            ->willReturn(['some' => 'value']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('elasticsearch')
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '1234',
                    'query' => [
                        'index' => 'magento2'
                    ]
                ],
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getTemplate')
            ->willReturn([
                'index' => [
                    'number_of_shards' => '1'
                ]
            ]);

        $this->assertSame(
            ['some' => 'value'],
            $this->model->get()
        );
    }

    public function testIsInstalled()
    {
        $this->managerMock->expects($this->exactly(2))
            ->method('has')
            ->with('smile/elasticsuite')
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->assertTrue($this->model->isInstalled());
        $this->assertFalse($this->model->isInstalled());
    }
}
