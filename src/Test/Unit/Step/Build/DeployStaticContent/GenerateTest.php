<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build\DeployStaticContent;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Step\Build\DeployStaticContent\Generate;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class GenerateTest extends TestCase
{
    /**
     * @var Generate
     */
    private $step;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CommandFactory|MockObject
     */
    private $commandFactoryMock;

    /**
     * @var Option|MockObject
     */
    private $optionMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $buildConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->optionMock = $this->createMock(Option::class);
        $this->buildConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->step = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->commandFactoryMock,
            $this->optionMock,
            $this->buildConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $commands = [
            'setup:static-content:deploy with locales',
            'setup:static-content:deploy with locales en_US',
        ];
        $this->optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['ua_UA', 'fr_FR', 'es_ES', 'en_US']);
        $this->optionMock->expects($this->once())
            ->method('getThreadCount')
            ->willReturn(3);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ["Generating static content for locales: ua_UA fr_FR es_ES en_US\nUsing 3 Threads"]
            );
        $this->commandFactoryMock->expects($this->once())
            ->method('matrix')
            ->with($this->optionMock, ['some_matrix'])
            ->willReturn($commands);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['setup:static-content:deploy with locales'],
                ['setup:static-content:deploy with locales en_US']
            );
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SCD_MATRIX)
            ->willReturn(['some_matrix']);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::BUILD_SCD_FAILED);

        $commands = [
            'setup:static-content:deploy with locales'
        ];
        $this->optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['ua_UA', 'fr_FR', 'es_ES', 'en_US']);
        $this->optionMock->expects($this->once())
            ->method('getThreadCount')
            ->willReturn(3);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ["Generating static content for locales: ua_UA fr_FR es_ES en_US\nUsing 3 Threads"]
            );
        $this->commandFactoryMock->expects($this->once())
            ->method('matrix')
            ->with($this->optionMock, ['some_matrix'])
            ->willReturn($commands);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->withConsecutive(
                ['setup:static-content:deploy with locales']
            )->willThrowException(new ShellException('Some error'));
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SCD_MATRIX)
            ->willReturn(['some_matrix']);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithGenericException()
    {
        $exceptionMessage = 'Some error';
        $exceptionCode = 111;
        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->expectExceptionCode($exceptionCode);

        $this->optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['ua_UA', 'fr_FR', 'es_ES', 'en_US']);
        $this->optionMock->expects($this->once())
            ->method('getThreadCount')
            ->willThrowException(new GenericException($exceptionMessage, $exceptionCode));
        $this->step->execute();
    }
}
