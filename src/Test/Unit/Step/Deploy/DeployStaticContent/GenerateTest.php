<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\DeployStaticContent;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\Deploy\DeployStaticContent\Generate;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Shell\ShellInterface;
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
    private $deployOptionMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->deployOptionMock = $this->createMock(Option::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->step = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->commandFactoryMock,
            $this->deployOptionMock,
            $this->stageConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt')
            ->willReturn(true);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ['Extracting locales'],
                ['Generating static content']
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithLocales(): void
    {
        $this->deployOptionMock->expects($this->exactly(2))
            ->method('getLocales')
            ->willReturn(['en_GB']);
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt')
            ->willReturn(true);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ['Extracting locales'],
                ['Generating static content for locales: en_GB']
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWitError(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::DEPLOY_SCD_FAILED);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt')
            ->willReturn(true);
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
            ->willThrowException(new ShellException('Some error'));
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_VERBOSE_COMMANDS, '-vvv'],
                [DeployInterface::VAR_SCD_MATRIX, []],
            ]);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFlagSetError(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Cannot update deployed version.');

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('magento_root/pub/static/deployed_version.txt')
            ->willReturn(false);

        $this->step->execute();
    }
}
