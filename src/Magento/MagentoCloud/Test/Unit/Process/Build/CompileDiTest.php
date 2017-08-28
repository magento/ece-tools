<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\CompileDi;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CompileDiTest extends TestCase
{
    /**
     * @var CompileDi
     */
    private $process;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildConfigMock = $this->getMockBuilder(Build::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new CompileDi(
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->buildConfigMock
        );
    }

    public function testExecute()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vvv');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(MAGENTO_ROOT . 'app/etc/config.php')
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::BUILD_OPT_SKIP_DI_COMPILATION)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running DI compilation');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento setup:di:compile -vvv');

        $this->process->execute();
    }

    public function testExecuteSkipCompilation()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vvv');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(MAGENTO_ROOT . 'app/etc/config.php')
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::BUILD_OPT_SKIP_DI_COMPILATION)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Skip running DI compilation');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Missing config.php file
     * @expectedExceptionCode 6
     */
    public function testExecuteMissedConfigFile()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vvv');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(MAGENTO_ROOT . 'app/etc/config.php')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                "Missing config.php, please run the following commands "
                . "\n 1. bin/magento module:enable --all "
                . "\n 2. git add -f app/etc/config.php "
                . "\n 3. git commit -a -m 'adding config.php' "
                . "\n 4. git push"
            );
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
