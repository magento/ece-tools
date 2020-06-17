<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Filesystem\Flag;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Step\Build\RunBaler;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RunBalerTest extends TestCase
{
    /**
     * @var RunBaler
     */
    private $step;

    /**
     * @var BuildInterface|MockObject
     */
    private $buildConfigMock;

    /**
     * @var Flag\Manager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var ValidatorInterface|MockObject
     */
    private $validatorMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->buildConfigMock = $this->createMock(BuildInterface::class);
        $this->flagManagerMock = $this->createMock(Flag\Manager::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->step = new RunBaler(
            $this->loggerMock,
            $this->buildConfigMock,
            $this->flagManagerMock,
            $this->validatorMock,
            $this->shellMock
        );
    }

    public function testBalerConfigDisabled(): void
    {
        $this->buildConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_USE_BALER)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Baler JS bundling is disabled.');

        $this->step->execute();
    }

    public function testScdNotRunYet(): void
    {
        $this->buildConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_USE_BALER)
            ->willReturn(true);
        $this->flagManagerMock->method('exists')
            ->with(Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Cannot run baler because static content has not been deployed.');

        $this->step->execute();
    }

    public function testValidatorFails(): void
    {
        $this->buildConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_USE_BALER)
            ->willReturn(true);
        $this->flagManagerMock->method('exists')
            ->with(Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->validatorMock->method('validate')
            ->willReturn(new Result\Error(
                'Baler validation failed',
                "Maybe baler isn't installed\nMaybe config is wrong"
            ));
        $this->loggerMock->expects($this->exactly(3))
            ->method('warning')
            ->withConsecutive(
                ['Baler validation failed'],
                [" - Maybe baler isn't installed"],
                [' - Maybe config is wrong']
            );

        $this->step->execute();
    }

    public function testRunBaler(): void
    {
        $this->buildConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_USE_BALER)
            ->willReturn(true);
        $this->flagManagerMock->method('exists')
            ->with(Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->validatorMock->method('validate')
            ->willReturn(new Result\Success());
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['Running Baler JS bundler.'], ['Baler JS bundling complete.']);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('baler');

        $this->step->execute();
    }

    public function testBalerThrowsException(): void
    {
        $this->buildConfigMock->method('get')
            ->with(BuildInterface::VAR_SCD_USE_BALER)
            ->willReturn(true);
        $this->flagManagerMock->method('exists')
            ->with(Flag\Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->validatorMock->method('validate')
            ->willReturn(new Result\Success());
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running Baler JS bundler.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('baler')
            ->willThrowException(new ShellException('Baler failed for some reason', 255));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Baler failed for some reason');
        $this->expectExceptionCode(Error::BUILD_BALER_NOT_FOUND);

        $this->step->execute();
    }
}
