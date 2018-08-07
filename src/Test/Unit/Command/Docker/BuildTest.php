<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Magento\MagentoCloud\Command\Docker\Build;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Docker\DevBuilder;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $command;

    /**
     * @var BuilderFactory|MockObject
     */
    private $builderFactoryMock;

    /**
     * @var DevBuilder|MockObject
     */
    private $builderMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->builderFactoryMock = $this->createMock(BuilderFactory::class);
        $this->builderMock = $this->createMock(DevBuilder::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->command = new Build(
            $this->builderFactoryMock,
            $this->fileMock,
            $this->fileListMock,
            $this->environmentMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->builderMock);
        $this->fileListMock->expects($this->once())
            ->method('getMagentoDockerCompose')
            ->willReturn('magento_root/docker-compose.yml');
        $this->builderMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_root/docker-compose.yml', "version: '2'\n");

        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteTestSet()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->method('getOption')
            ->willReturnMap([
                [Build::OPTION_IS_TEST, true],
            ]);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->builderMock);
        $this->fileListMock->expects($this->once())
            ->method('getToolsDockerCompose')
            ->willReturn('ece_root/docker-compose.yml');
        $this->builderMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('ece_root/docker-compose.yml', "version: '2'\n");

        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteWithParams()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->builderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->builderMock);
        $this->fileListMock->expects($this->once())
            ->method('getMagentoDockerCompose')
            ->willReturn('magento_rood/docker-compose.yml');
        $this->builderMock->expects($this->once())
            ->method('build')
            ->willReturn(['version' => '2']);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('magento_rood/docker-compose.yml', "version: '2'\n");
        $inputMock->method('getOption')
            ->willReturnMap([
                [Build::OPTION_PHP, '7.1'],
                [Build::OPTION_DB, '10'],
                [Build::OPTION_NGINX, '1.9'],
                [Build::OPTION_IS_TEST, false],
            ]);
        $this->builderMock->expects($this->once())
            ->method('setPhpVersion')
            ->with('7.1');
        $this->builderMock->expects($this->once())
            ->method('setNginxVersion')
            ->with('1.9');
        $this->builderMock->expects($this->once())
            ->method('setDbVersion')
            ->with('10');

        $this->command->execute($inputMock, $outputMock);
    }

    public function testIsEnabled()
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('isMasterBranch')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertFalse($this->command->isEnabled());
        $this->assertTrue($this->command->isEnabled());
    }
}
