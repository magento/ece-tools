<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\WarmUp\UrlsPattern\ConfigShowUrlCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ConfigShowUrlCommandTest extends TestCase
{
    /**
     * @var ConfigShowUrlCommand
     */
    private $configShowUrlCommand;

    /**
     * @var ShellInterface|MockObject
     */
    private $magentoShellMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->configShowUrlCommand = new ConfigShowUrlCommand($shellFactoryMock);
    }

    public function testExecute()
    {
        $arguments = ['--some-option=2'];
        $urls = ['www.example.com', 'www.example2.com'];

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn(json_encode($urls));
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:urls', $arguments)
            ->willReturn($processMock);

        $this->assertEquals(
            $urls,
            $this->configShowUrlCommand->execute($arguments)
        );
    }

    public function testExecuteWithWrongJson()
    {
        $this->expectException(GenericException::class);
        $this->expectExceptionMessage('Can\'t parse result from command config:show:urls: Syntax error');

        $arguments = ['--some-option=2'];

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('{bad: json');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:urls', $arguments)
            ->willReturn($processMock);

        $this->configShowUrlCommand->execute($arguments);
    }
}
