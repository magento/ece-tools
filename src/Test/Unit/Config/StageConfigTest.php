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
