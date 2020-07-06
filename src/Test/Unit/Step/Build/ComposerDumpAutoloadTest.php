<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Step\Build\ComposerDumpAutoload;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var ShellInterface|MockObject
     */
    private $shell;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shell = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        $this->step = new ComposerDumpAutoload(
            $this->shell
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('something went wrong');
        $this->expectExceptionCode(Error::BUILD_COMPOSER_DUMP_AUTOLOAD_FAILED);

        $this->shell->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction')
            ->willThrowException(new ShellException('something went wrong'));

        $this->step->execute();
    }
}
