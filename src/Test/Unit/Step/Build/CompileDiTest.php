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
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\Build\CompileDi;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
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
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->step = new CompileDi(
            $this->loggerMock,
            $shellFactoryMock,
            $this->stageConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Running DI compilation'],
                ['End of running DI compilation']
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('setup:di:compile', ['-vvv']);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConfigException()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willThrowException(new ConfigException('some error', Error::BUILD_CONFIG_NOT_DEFINED));

        $this->expectExceptionCode(Error::BUILD_CONFIG_NOT_DEFINED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException()
    {
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException('some error'));

        $this->expectExceptionCode(Error::BUILD_DI_COMPILATION_FAILED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }
}
