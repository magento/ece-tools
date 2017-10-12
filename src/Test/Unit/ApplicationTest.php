<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Unit;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\PreStart;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ApplicationTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $containerMock;

    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $composerMock;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packageMock;

    /**
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildCommandMock;

    /**
     * @var PreStart|\PHPUnit_Framework_MockObject_MockObject
     */
    private $preStartCommandMock;

    /**
     * @var Deploy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deployCommandMock;

    /**
     * @var ConfigDump|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configDumpCommand;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->buildCommandMock = $this->createMock(Build::class);
        $this->preStartCommandMock = $this->createMock(PreStart::class);
        $this->deployCommandMock = $this->createMock(Deploy::class);
        $this->configDumpCommand = $this->createMock(ConfigDump::class);

        $this->buildCommandMock->method('getName')
            ->willReturn(Build::NAME);
        $this->buildCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->buildCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->buildCommandMock->method('getAliases')
            ->willReturn([]);

        $this->preStartCommandMock->method('getName')
            ->willReturn(PreStart::NAME);
        $this->preStartCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->preStartCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->preStartCommandMock->method('getAliases')
            ->willReturn([]);

        $this->deployCommandMock->method('getName')
            ->willReturn(Deploy::NAME);
        $this->deployCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->deployCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->deployCommandMock->method('getAliases')
            ->willReturn([]);

        $this->configDumpCommand->method('getName')
            ->willReturn(ConfigDump::NAME);
        $this->configDumpCommand->method('isEnabled')
            ->willReturn(true);
        $this->configDumpCommand->method('getDefinition')
            ->willReturn([]);
        $this->configDumpCommand->method('getAliases')
            ->willReturn([]);

        $this->containerMock->method('get')
            ->willReturnMap([
                [Composer::class, $this->composerMock],
                [Build::class, $this->buildCommandMock],
                [PreStart::class, $this->preStartCommandMock],
                [Deploy::class, $this->deployCommandMock],
                [ConfigDump::class, $this->configDumpCommand],
            ]);
        $this->composerMock->method('getPackage')
            ->willReturn($this->packageMock);

        $this->application = new Application(
            $this->containerMock
        );
    }

    public function testHasCommand()
    {
        $this->assertTrue($this->application->has(Build::NAME));
        $this->assertTrue($this->application->has(PreStart::NAME));
        $this->assertTrue($this->application->has(Deploy::NAME));
        $this->assertTrue($this->application->has(ConfigDump::NAME));
    }
}
