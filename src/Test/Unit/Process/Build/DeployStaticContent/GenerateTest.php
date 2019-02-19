<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Process\Build\DeployStaticContent\Generate;
use Magento\MagentoCloud\Process\ProcessException;
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
    private $process;

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
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->optionMock = $this->createMock(Option::class);
        $this->buildConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->process = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->commandFactoryMock,
            $this->optionMock,
            $this->buildConfigMock
        );
    }

    /**
     * @throws ProcessException
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

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Some error
     *
     * @throws ProcessException
     */
    public function testExecuteWithException()
    {
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

        $this->process->execute();
    }
}
