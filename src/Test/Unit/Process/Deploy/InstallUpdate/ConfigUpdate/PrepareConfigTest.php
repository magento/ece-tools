<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\PrepareConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareConfigTest extends TestCase
{
    /**
     * @var PrepareConfig
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

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
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->process = new PrepareConfig(
            $this->loggerMock,
            $this->configWriterMock,
            $this->globalConfigMock
        );
    }

    /**
     * @param bool $scdOnDemand
     * @param bool $skipHtmlMinification
     * @param array $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $scdOnDemand, bool $skipHtmlMinification, array $expectedResult)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php.');
        $this->globalConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [GlobalConfig::VAR_SCD_ON_DEMAND, $scdOnDemand],
                [GlobalConfig::VAR_SKIP_HTML_MINIFICATION, $skipHtmlMinification],
            ]);
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
                'skipHtmlMinification' => true,
                'expectedResult' => [
                    'static_content_on_demand_in_production' => 0,
                    'force_html_minification' => 1,
                ],
            ],
            [
                'scdOnDemand' => true,
                'skipHtmlMinification' => false,
                'expectedResult' => [
                    'static_content_on_demand_in_production' => 1,
                    'force_html_minification' => 0,
                ],
            ],
        ];
    }
}
