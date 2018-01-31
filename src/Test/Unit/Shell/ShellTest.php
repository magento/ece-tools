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
     * @param int $loggerInfoExpects
     * @dataProvider executeDataProvider
     */
    public function testExecute($execOutput, $loggerInfoExpects)
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

        $this->loggerMock->expects($this->exactly($loggerInfoExpects))
            ->method('info')
            ->withConsecutive(
                ['Command: ' . $command],
                ['Status: 0'],
                ['Output: ' . var_export($execOutput, true)]
            );

        $this->shell->execute($command);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'execOutput' => null,
                'loggerInfoExpects' => 2,
            ],
            [
                'execOutput' => 'test',
                'loggerInfoExpects' => 3,
            ],
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
                $output = null;
            });

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Command: ' . $command],
                ['Status: 123']
            );

        $this->shell->execute($command);
    }
}
