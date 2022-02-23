<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ModuleRefresh;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ModuleRefreshTest extends TestCase
{
    /**
     * @var ModuleRefresh
     */
    private $command;

    /**
     * @var Module|MockObject
     */
    private $moduleMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->moduleMock = $this->createMock(Module::class);

        $this->command = new ModuleRefresh(
            $this->moduleMock
        );
    }

    /**
     * @throws FileSystemException
     * @throws ShellException
     */
    public function testExecute(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->with("The following modules have been enabled:\nMagento_Demo");

        $this->moduleMock->expects($this->once())
            ->method('refresh')
            ->willReturn(['Magento_Demo']);

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @throws FileSystemException
     * @throws ShellException
     */
    public function testExecuteNoModules(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->with('No modules were changed.');

        $this->moduleMock->expects($this->once())
            ->method('refresh')
            ->willReturn([]);

        $this->command->execute($inputMock, $outputMock);
    }
}
