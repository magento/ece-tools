<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\StageConfig;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class StageConfigTest extends TestCase
{
    /**
     * @var StageConfig
     */
    private $stageConfig;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var EnvironmentConfig|Mock
     */
    private $environmentConfigMock;

    /**
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);

        $this->stageConfig = new StageConfig(
            $this->environmentReaderMock,
            $this->environmentConfigMock,
            $this->buildConfigMock
        );
    }

    /**
     * @param string $configName
     * @param mixed $configValue
     * @dataProvider buildExistsDataProvider
     */
    public function testBuildExists(string $configName, $configValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfig::STAGE_BUILD => [
                    $configName => $configValue,
                ],
            ]);

        $this->assertSame($configValue, $this->stageConfig->getBuild($configName));
    }

    /**
     * @return array
     */
    public function buildExistsDataProvider(): array
    {
        return [
            ['testName', 'testValue'],
        ];
    }

    /**
     * @param string $configName
     * @param mixed $configValue
     * @dataProvider deployExistsDataProvider
     */
    public function testDeployExists(string $configName, $configValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfig::STAGE_DEPLOY => [
                    $configName => $configValue,
                ],
            ]);

        $this->assertSame($configValue, $this->stageConfig->getDeploy($configName));
    }

    public function deployExistsDataProvider(): array
    {
        return [
            ['testName', 'testValue'],
        ];
    }

    /**
     * @param string $stage
     * @param string $name
     * @param mixed $expectedValue
     * @dataProvider getDefaultDataProvider
     */
    public function testGetDefault(string $stage, string $name, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $this->assertSame($expectedValue, $this->stageConfig->get($stage, $name));
    }

    /**
     * @return array
     */
    public function getDefaultDataProvider(): array
    {
        return [
            [
                StageConfigInterface::STAGE_BUILD,
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                6,
            ],
            [
                StageConfigInterface::STAGE_DEPLOY,
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                4,
            ],
            [
                StageConfigInterface::STAGE_BUILD,
                StageConfigInterface::VAR_SCD_STRATEGY,
                '',
            ],
            [
                StageConfigInterface::STAGE_DEPLOY,
                StageConfigInterface::VAR_SCD_STRATEGY,
                '',
            ],
        ];
    }

    /**
     * @expectedExceptionMessage Default config value for build:undefined var was not provided
     * @expectedException \RuntimeException
     */
    public function testGetBuildWithDefaultException()
    {
        $this->stageConfig->get(StageConfig::STAGE_BUILD, 'undefined var');
    }

    /**
     * @expectedExceptionMessage Default config value for deploy:undefined var was not provided
     * @expectedException \RuntimeException
     */
    public function testGetDeployWithDefaultException()
    {
        $this->stageConfig->get(StageConfig::STAGE_DEPLOY, 'undefined var');
    }
}
