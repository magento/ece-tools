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
     * @param array $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $scdOnDemand, array $expectedResult)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php SCD on demand in production.');
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
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
                'expectedResult' => ['static_content_on_demand_in_production' => 0]
            ],
            [
                'scdOnDemand' => true,
                'expectedResult' => ['static_content_on_demand_in_production' => 1]
            ],
        ];
    }
}
