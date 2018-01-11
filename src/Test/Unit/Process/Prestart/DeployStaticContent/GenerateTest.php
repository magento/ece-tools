<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Prestart\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Prestart\DeployStaticContent\Generate;
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
     * @var CommandFactory|Mock
     */
    private $commandFactoryMock;

    /**
     * @var Option|Mock
     */
    private $deployOptionMock;

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
        $this->commandFactoryMock = $this->createMock(CommandFactory::class);
        $this->deployOptionMock = $this->createMock(Option::class);

        $this->process = new Generate(
            $this->shellMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->commandFactoryMock,
            $this->deployOptionMock
        );
    }

    public function testExecute()
    {
        $this->deployOptionMock->expects($this->once())
            ->method('getLocales')
            ->willReturn(['ua_UA', 'fr_FR', 'es_ES', 'en_US']);
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
            ->method('create')
            ->with($this->deployOptionMock)
            ->willReturn('some command');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('some command');

        $this->process->execute();
    }
}
