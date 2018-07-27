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
use Monolog\Logger;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount as InvokedCountMatcher;

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
                ->method('log')
                ->with(Logger::DEBUG, PHP_EOL . '  ' . $execOutput[0]);
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
     * @param InvokedCountMatcher $logExpects
     * @param array $execOutput
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Command ls -al returned code 123
     * @expectedExceptionCode 123
     * @dataProvider executeExceptionDataProvider
     */
    public function testExecuteException($logExpects, array $execOutput)
    {
        $testCase = $this;
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($testCase, $execCommand, $execOutput) {
                $testCase->assertSame($execCommand, $cmd);
                $status = 123;
                $output = $execOutput;
            });

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);
        $this->loggerMock->expects($logExpects)
            ->method('log')
            ->with(Logger::CRITICAL, PHP_EOL . '  test');

        $this->shell->execute($command);
    }

    /**
     * @return array
     */
    public function executeExceptionDataProvider()
    {
        return [
            [
                'logExpects' => $this->never(),
                'execOutput' => []
            ],
            [
                'logExpects' => $this->once(),
                'execOutput' => ['test']
            ],
        ];
    }
}
