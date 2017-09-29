<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent\Generate;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
use Magento\MagentoCloud\StaticContent\Command;
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
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var Command|Mock
     */
    private $commandMock;

    /**
     * @var Option|Mock
     */
    private $deployOption;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->commandMock = $this->createMock(Command::class);
        $this->deployOption = $this->createMock(Option::class);

        $this->process = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->commandMock,
            $this->deployOption
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
        $this->commandMock->expects($this->once())
            ->method('create')
            ->willReturn('php ./bin/magento static:content:deploy:command');
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable  -vvv '],
                ['php ./bin/magento static:content:deploy:command'],
                ['php ./bin/magento maintenance:disable  -vvv ']
            );
        $this->environmentMock->method('getVerbosityLevel')
            ->willReturn(' -vvv ');

        $this->process->execute();
    }
}
