<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Step\Build\ComposerDumpAutoload;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
    private $shellMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->step = new ComposerDumpAutoload(
            $this->shellMock,
            $this->stageConfigMock,
            $this->loggerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_COMPOSER_DUMP_AUTOLOAD)
            ->willReturn(false);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteDumpSkipped(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_COMPOSER_DUMP_AUTOLOAD)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'The composer dump-autoload command was skipped as SKIP_COMPOSER_DUMP_AUTOLOAD variable is set to true'
            );
        $this->shellMock->expects($this->never())
            ->method('execute');

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

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('composer dump-autoload -o --ansi --no-interaction')
            ->willThrowException(new ShellException('something went wrong'));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConfigException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('something went wrong');
        $this->expectExceptionCode(10);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('something went wrong', 10));
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }
}
