<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Build\PreBuild;
use Magento\MagentoCloud\Package\Manager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class PreBuildTest extends TestCase
{
    /**
     * @var PreBuild
     */
    private $process;

    /**
     * @var Build|Mock
     */
    private $buildConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Manager|Mock
     */
    private $packageManagerMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->buildConfigMock = $this->createMock(Build::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->createMock(Manager::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->process = new PreBuild(
            $this->buildConfigMock,
            $this->loggerMock,
            $this->packageManagerMock,
            $this->flagManagerMock
        );
    }

    /**
     * @param string $verbosity
     * @param string $expectedVerbosity
     * @dataProvider executeDataProvider
     */
    public function testExecute(string $verbosity, string $expectedVerbosity)
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn($verbosity);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Verbosity level is ' . $expectedVerbosity],
                ['Starting build. Some info.']
            );
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(StaticContentDeployInBuild::KEY);
        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('Some info.');

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'verbosity very' => [' -vvv', ' -vvv'],
            'verbosity none' => ['', 'not set'],
        ];
    }
}
