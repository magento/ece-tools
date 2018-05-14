<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\Config;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * @param array $magentoCloudVariables
     * @param array $envDbConfig
     * @param array $relationships
     * @param boolean $setSlave
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $magentoCloudVariables,
        array $envDbConfig,
        array $relationships,
        $setSlave,
        array $expectedConfig
    ) {
        /** @var Environment|Mock $dbConfigEnvironmentMock */
        $dbConfigEnvironmentMock = $this->createMock(Environment::class);
        $dbConfigEnvironmentMock->expects($this->any())
            ->method('getVariables')
            ->willReturn($magentoCloudVariables);

        /** @var Environment|Mock $readConnectionEnvironmentMock */
        $readConnectionEnvironmentMock = $this->createMock(Environment::class);
        $readConnectionEnvironmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn($relationships);

        $readConnection = new ReadConnection($readConnectionEnvironmentMock);

        $dbConfig = new Config(
            $dbConfigEnvironmentMock,
            $readConnection,
            $this->stageConfigMock,
            $this->loggerMock
        );

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_DATABASE_CONFIGURATION)
            ->willReturn($envDbConfig);

        $this->assertEquals($expectedConfig, $dbConfig->get());
    }

    public function getDataProvider()
    {
        return [
            [
                [],
                [],
                [],
                [],
            ]
        ];
    }
}
