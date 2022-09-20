<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\Build\EnableEventing;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class EnableEventingTest extends TestCase
{
    /**
     * @var EnableEventing
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
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

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
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->step = new EnableEventing(
            $this->loggerMock,
            $shellFactoryMock,
            $this->globalConfigMock
        );
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteEventingNotEnabled()
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_EVENTING)
            ->willReturn(false);

        $this->magentoShellMock->expects(self::never())
            ->method('execute');
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteGenerateCommandFailed()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('error during module generation');
        $this->expectExceptionCode(Error::GLOBAL_EVENTING_MODULE_GENERATE_FAILED);

        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_EVENTING)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with('events:generate:module')
            ->willThrowException(new ShellException('error during module generation'));
        $this->loggerMock->expects(self::once())
            ->method('notice');
        $this->loggerMock->expects(self::once())
            ->method('error');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteEnableModuleCommandFailed()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('error during module enablement');
        $this->expectExceptionCode(Error::GLOBAL_EVENTING_MODULE_ENABLEMENT_FAILED);

        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_EVENTING)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::at(0))
            ->method('execute')
            ->with('events:generate:module');
        $this->magentoShellMock->expects(self::at(1))
            ->method('execute')
            ->with('module:enable Magento_AdobeCommerceEvents')
            ->willThrowException(new ShellException('error during module enablement'));
        $this->loggerMock->expects(self::exactly(2))
            ->method('notice');
        $this->loggerMock->expects(self::once())
            ->method('error');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteSuccess()
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_EVENTING)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::at(0))
            ->method('execute')
            ->with('events:generate:module');
        $this->magentoShellMock->expects(self::at(1))
            ->method('execute')
            ->with('module:enable Magento_AdobeCommerceEvents');
        $this->loggerMock->expects(self::exactly(2))
            ->method('notice');
        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->step->execute();
    }
}
