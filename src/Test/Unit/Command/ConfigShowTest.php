<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\Command\ConfigShow;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ConfigShowTest extends TestCase
{
    /**
     * @var ConfigShow
     */
    private $command;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigShow\Renderer|MockObject
     */
    private $configRendererMock;

    /**
     * @var InputInterface|MockObject
     */
    private $inputMock;

    /**
     * @var OutputInterface|MockObject
     */
    private $outputMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configRendererMock = $this->createMock(ConfigShow\Renderer::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $this->outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->command = new ConfigShow(
            $this->configRendererMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->inputMock->expects($this->once())
            ->method('getArgument')
            ->with('variable')
            ->willReturn([ConfigShow::RELATIONSHIPS]);
        $this->configRendererMock->expects($this->once())
            ->method('printRelationships');
        $this->configRendererMock->expects($this->never())
            ->method('printRoutes');
        $this->configRendererMock->expects($this->never())
            ->method('printVariables');
        $this->outputMock->expects($this->never())
            ->method('writeln');

        $this->command->execute($this->inputMock, $this->outputMock);
    }

    public function testExecuteWithWrongVariables()
    {
        $this->inputMock->expects($this->once())
            ->method('getArgument')
            ->with('variable')
            ->willReturn(['wrong-variable']);
        $this->configRendererMock->expects($this->never())
            ->method($this->anything());
        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with('<error>Unknown variable(s): wrong-variable</error>');

        $this->command->execute($this->inputMock, $this->outputMock);
    }

    public function testExecuteWithEmptyVariables()
    {
        $this->inputMock->expects($this->once())
            ->method('getArgument')
            ->with('variable')
            ->willReturn([]);
        $this->configRendererMock->expects($this->once())
            ->method('printRelationships');
        $this->configRendererMock->expects($this->once())
            ->method('printRoutes');
        $this->configRendererMock->expects($this->once())
            ->method('printVariables');
        $this->outputMock->expects($this->never())
            ->method('writeln');

        $this->command->execute($this->inputMock, $this->outputMock);
    }
}
