<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Step\Build\ComposerDumpAutoload;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ComposerDumpAutoloadTest extends TestCase
{
    /**
     * @var ComposerDumpAutoload
     */
    private $step;

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

        $this->step = new ComposerDumpAutoload(
            $this->shell
        );
    }

    public function testExecute()
    {
        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction');

        $this->step->execute();
    }

    public function testExecuteWithException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('something went wrong');

        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction')
            ->willThrowException(new ShellException('something went wrong'));

        $this->step->execute();
    }
}
