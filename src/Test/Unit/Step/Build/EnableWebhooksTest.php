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
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\Build\EnableWebhooks;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class EnableWebhooksTest extends TestCase
{
    /**
     * @var EnableWebhooks
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
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->step = new EnableWebhooks(
            $this->loggerMock,
            $shellFactoryMock,
            $this->globalConfigMock
        );
        $this->processMock = $this->createMock(ProcessInterface::class);
    }

    /**
     * @return void
     * @throws StepException
     */
    public function testExecuteWebhooksNotEnabled()
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_WEBHOOKS)
            ->willReturn(false);

        $this->magentoShellMock->expects(self::never())
            ->method('execute');
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws StepException
     */
    public function testExecuteGenerateCommandFailed()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('error during module generation');
        $this->expectExceptionCode(Error::GLOBAL_WEBHOOKS_MODULE_GENERATE_FAILED);

        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_WEBHOOKS)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with('webhooks:generate:module')
            ->willThrowException(new ShellException('error during module generation'));
        $this->loggerMock->expects(self::once())
            ->method('notice');
        $this->loggerMock->expects(self::once())
            ->method('error');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws StepException
     */
    public function testExecuteEnableModuleCommandFailed()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('error during module enablement');
        $this->expectExceptionCode(Error::GLOBAL_WEBHOOKS_MODULE_ENABLEMENT_FAILED);

        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_WEBHOOKS)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($arg1) {
                if ($arg1 == 'webhooks:generate:module') {
                    return $this->processMock;
                } elseif ($arg1 == 'module:enable Magento_AdobeCommerceWebhookPlugins') {
                    throw new ShellException('error during module enablement');
                }
            });
        $this->loggerMock->expects(self::exactly(2))
            ->method('notice');
        $this->loggerMock->expects(self::once())
            ->method('error');

        $this->step->execute();
    }

    /**
     * @return void
     * @throws StepException
     */
    public function testExecuteSuccess()
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(StageConfigInterface::VAR_ENABLE_WEBHOOKS)
            ->willReturn(true);
        $this->magentoShellMock->expects(self::any())
            ->method('execute')
            ->willReturnCallback(function ($arg1) {
                if ($arg1 == 'webhooks:generate:module' ||
                    $arg1 == 'module:enable Magento_AdobeCommerceWebhookPlugins') {
                    return $this->processMock;
                }
            });
        $this->loggerMock->expects(self::exactly(2))
            ->method('notice');
        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->step->execute();
    }
}
