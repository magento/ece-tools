<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Shell;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Shell\Shell;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ShellTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shell = new Shell($this->loggerMock, $this->directoryListMock);
    }

    /**
     * @param string $execOutput
     * @dataProvider executeDataProvider
     */
    public function testExecute($execOutput)
    {
        $testCase = $this;
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($testCase, $execCommand, $execOutput) {
                $testCase->assertSame($execCommand, $cmd);
                $status = 0;
                $output = $execOutput;
            });

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);
        if ($execOutput) {
            $this->loggerMock->expects($this->once())
                ->method('debug')
                ->with(PHP_EOL . '  ' . $execOutput[0]);
        }

        $this->shell->execute($command);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [ 'execOutput' => [] ],
            [ 'execOutput' => ['test'] ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Command ls -al returned code 123
     * @expectedExceptionCode 123
     */
    public function testExecuteException()
    {
        $testCase = $this;
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($testCase, $execCommand) {
                $testCase->assertSame($execCommand, $cmd);
                $status = 123;
                $output = [];
            });

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);

        $this->shell->execute($command);
    }
}
