<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\ComposerDumpAutoload;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ComposerDumpAutoloadTest extends TestCase
{
    /**
     * @var ComposerDumpAutoload
     */
    private $process;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shell;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shell = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        $this->process = new ComposerDumpAutoload(
            $this->shell
        );
    }

    public function testExecute()
    {
        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction');

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage something went wrong
     */
    public function testExecuteWithException()
    {
        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction')
            ->willThrowException(new ShellException('something went wrong'));

        $this->process->execute();
    }
}
