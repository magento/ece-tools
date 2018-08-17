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
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
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
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var CommandFactory|Mock
     */
    private $commandFactoryMock;

    /**
     * @var Option|Mock
     */
    private $deployOption;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
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
                ['Extracting locales'],
                ['Generating static content for locales: en_GB fr_FR']
            );
        $this->commandFactoryMock->expects($this->once())
            ->method('matrix')
            ->willReturn([
                'php ./bin/magento static:content:deploy:command --ansi --no-interaction',
            ]);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento static:content:deploy:command --ansi --no-interaction');
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_VERBOSE_COMMANDS, '-vvv'],
                [DeployInterface::VAR_SCD_MATRIX, []],
            ]);

        $this->process->execute();
    }
}
