<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\PrepareConfig;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareConfigTest extends TestCase
{
    /**
     * @var PrepareConfig
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->step = new PrepareConfig(
            $this->loggerMock,
            $this->configWriterMock,
            $this->globalConfigMock
        );
    }

    /**
     * @param bool $scdOnDemand
     * @param bool $skipHtmlMinification
     * @param null|string $xFrameOptions
     * @param array $expectedResult
     *
     * @throws StepException
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool $scdOnDemand,
        bool $skipHtmlMinification,
        $xFrameOptions,
        array $expectedResult
    ): void {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php.');
        $this->globalConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [GlobalConfig::VAR_SCD_ON_DEMAND, $scdOnDemand],
                [GlobalConfig::VAR_SKIP_HTML_MINIFICATION, $skipHtmlMinification],
                [GlobalConfig::VAR_X_FRAME_CONFIGURATION, $xFrameOptions]
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with($expectedResult);

        $this->step->execute();
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
                'xFrameOptions' => null,
                'expectedResult' => [
                    'static_content_on_demand_in_production' => 0,
                    'force_html_minification' => 1,
                ],
            ],
            [
                'scdOnDemand' => true,
                'skipHtmlMinification' => false,
                'xFrameOptions' => 'ALLOW-FROM google.com',
                'expectedResult' => [
                    'static_content_on_demand_in_production' => 1,
                    'force_html_minification' => 0,
                    'x-frame-options' => 'ALLOW-FROM google.com'
                ],
            ],
        ];
    }

    /**
     * @param bool $scdOnDemand
     * @param bool $skipHtmlMinification
     * @param null|string $xFrameOptions
     * @param array $expectedResult
     *
     * @throws StepException
     *
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithException(
        bool $scdOnDemand,
        bool $skipHtmlMinification,
        $xFrameOptions,
        array $expectedResult
    ): void {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php.');
        $this->globalConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [GlobalConfig::VAR_SCD_ON_DEMAND, $scdOnDemand],
                [GlobalConfig::VAR_SKIP_HTML_MINIFICATION, $skipHtmlMinification],
                [GlobalConfig::VAR_X_FRAME_CONFIGURATION, $xFrameOptions]
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with($expectedResult)
            ->willThrowException(new FileSystemException('Some error'));

        $this->step->execute();
    }
}
