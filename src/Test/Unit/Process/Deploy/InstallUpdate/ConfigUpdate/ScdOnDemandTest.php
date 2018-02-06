<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\ScdOnDemand;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ScdOnDemandTest extends TestCase
{
    /**
     * @var ScdOnDemand
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var GlobalConfig|Mock
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->process = new ScdOnDemand(
            $this->loggerMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->globalConfigMock
        );
    }

    /**
     * @param bool $scdOnDemand
     * @param array $config
     * @param array $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $scdOnDemand, array $config, array $expectedResult)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php SCD on demand in production.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn($scdOnDemand);
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with($expectedResult);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'scdOnDemand' => false,
                'config' => [],
                'expectedResult' => ['static_content_on_demand_in_production' => 0]
            ],
            [
                'scdOnDemand' => true,
                'config' => [],
                'expectedResult' => ['static_content_on_demand_in_production' => 1]
            ],
            [
                'scdOnDemand' => false,
                'config' => ['static_content_on_demand_in_production' => 1],
                'expectedResult' => ['static_content_on_demand_in_production' => 0]
            ],
            [
                'scdOnDemand' => true,
                'config' => ['static_content_on_demand_in_production' => 0],
                'expectedResult' => ['static_content_on_demand_in_production' => 1]
            ],
            [
                'scdOnDemand' => false,
                'config' => ['something' => true],
                'expectedResult' => ['something' => true, 'static_content_on_demand_in_production' => 0]
            ],
            [
                'scdOnDemand' => true,
                'config' => ['something' => false],
                'expectedResult' => ['something' => false, 'static_content_on_demand_in_production' => 1]
            ],
        ];
    }
}
