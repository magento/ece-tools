<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent\Generate;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
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
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var CommandFactory|MockObject
     */
    private $commandFactoryMock;

    /**
     * @var Option|MockObject
     */
    private $deployOption;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->createMock(ExecBinMagento::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->deployOption = $this->createMock(Option::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->commandFactoryMock,
            $this->deployOption,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt');
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Enabling Maintenance mode'],
                ['Extracting locales'],
                ['Generating static content for locales: en_GB fr_FR'],
                ['Maintenance mode is disabled.']
            );
        $this->commandFactoryMock->expects($this->once())
            ->method('matrix')
            ->willReturn([
                ['--strategy=quick', '--theme=Magento/backend', 'en_US']
            ]);
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['maintenance:enable', '-vvv'],
                ['setup:static-content:deploy', ['--strategy=quick', '--theme=Magento/backend', 'en_US']],
                ['maintenance:disable', '-vvv']
            );
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_VERBOSE_COMMANDS, '-vvv'],
                [DeployInterface::VAR_SCD_MATRIX, []],
            ]);

        $this->process->execute();
    }
}
