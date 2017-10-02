<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Build\DeployStaticContent\Generate;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
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
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var CommandFactory|Mock
     */
    private $commandFactoryMock;

    /**
     * @var Option|Mock
     */
    private $optionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->optionMock = $this->createMock(Option::class);

        $this->process = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->commandFactoryMock,
            $this->optionMock
        );
    }

    public function testExecute()
    {
        $this->optionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['ua_UA', 'fr_FR', 'es_ES', 'en_US']);
        $this->optionMock->expects($this->once())
            ->method('getTreadCount')
            ->willReturn(3);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ["Generating static content for locales: ua_UA fr_FR es_ES en_US\nUsing 3 Threads"]
            );
        $this->commandFactoryMock->expects($this->once())
            ->method('createParallel')
            ->with($this->optionMock)
            ->willReturn('some parallel command');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(
                "printf 'some parallel command' | xargs -I CMD -P 3 bash -c CMD"
            );

        $this->process->execute();
    }
}
