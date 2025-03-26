<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
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
    private $elasticSuite;

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
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(Manager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->configMergerMock = $this->createTestProxy(ConfigMerger::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->elasticSuite = new ElasticSuite(
            $this->managerMock,
            $this->stageConfigMock,
            $this->configMergerMock,
            $this->elasticSearchMock
        );
    }

    public function testGetNoES(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTICSUITE_CONFIGURATION)
            ->willReturn(['some' => 'value']);
        $this->elasticSearchMock->expects($this->never())
            ->method('getConfiguration');

        $this->assertSame(
            ['some' => 'value'],
            $this->elasticSuite->get()
        );
    }

    public function testGet(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTICSUITE_CONFIGURATION)
            ->willReturn([]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'host' => '127.0.0.1',
                'port' => '1234',
                'query' => [
                    'index' => 'magento2'

                ]
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
            $this->elasticSuite->get()
        );
    }

    public function testGetOnlyReplica(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTICSUITE_CONFIGURATION)
            ->willReturn(['some' => 'value', StageConfigInterface::OPTION_MERGE => true]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'host' => '127.0.0.1',
                'port' => '1234',
                'query' => [
                    'index' => 'magento2'
                ]
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getTemplate')
            ->willReturn([
                'index' => [
                    'number_of_replicas' => '2',
                ]
            ]);

        $this->assertSame(
            [
                'es_client' => [
                    'servers' => '127.0.0.1:1234',
                    'indices_alias' => 'magento2'
                ],
                'indices_settings' => [
                    'number_of_replicas' => 2
                ],
                'some' => 'value'
            ],
            $this->elasticSuite->get()
        );
    }

    public function testGetOnlyShards(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ELASTICSUITE_CONFIGURATION)
            ->willReturn(['some' => 'value', StageConfigInterface::OPTION_MERGE => true]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'host' => '127.0.0.1',
                'port' => '1234',
                'query' => [
                    'index' => 'magento2'
                ]
            ]);
        $this->elasticSearchMock->expects($this->once())
            ->method('getTemplate')
            ->willReturn([
                'index' => [
                    'number_of_shards' => '1'
                ]
            ]);

        $this->assertSame(
            [
                'es_client' => [
                    'servers' => '127.0.0.1:1234',
                    'indices_alias' => 'magento2'
                ],
                'indices_settings' => [
                    'number_of_shards' => 1
                ],
                'some' => 'value'
            ],
            $this->elasticSuite->get()
        );
    }

    public function testIsInstalled(): void
    {
        $this->managerMock->expects($this->exactly(2))
            ->method('has')
            ->with('smile/elasticsuite')
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->assertTrue($this->elasticSuite->isInstalled());
        $this->assertFalse($this->elasticSuite->isInstalled());
    }

    public function testIsAvailable(): void
    {
        $this->elasticSearchMock->expects($this->exactly(3))
            ->method('isInstalled')
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                true
            );
        $this->managerMock->expects($this->exactly(2))
            ->method('has')
            ->with('smile/elasticsuite')
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false
            );

        $this->assertTrue($this->elasticSuite->isAvailable());
        $this->assertFalse($this->elasticSuite->isAvailable());
        $this->assertFalse($this->elasticSuite->isAvailable());
    }
}
