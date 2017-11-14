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
use Magento\MagentoCloud\Command\DbDump;
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
     * @var Deploy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deployCommandMock;

    /**
     * @var $dbDumpCommandMock;
     */
    private $dbDumpCommandMock;

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
        $this->deployCommandMock = $this->createMock(Deploy::class);
        $this->dbDumpCommandMock = $this->createMock(DbDump::class);
        $this->configDumpCommand = $this->createMock(Deploy::class);

        $this->buildCommandMock->method('getName')
            ->willReturn(Build::NAME);
        $this->buildCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->buildCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->buildCommandMock->method('getAliases')
            ->willReturn([]);
        $this->deployCommandMock->method('getName')
            ->willReturn(Deploy::NAME);
        $this->deployCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->deployCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->deployCommandMock->method('getAliases')
            ->willReturn([]);
        $this->dbDumpCommandMock->method('getName')
            ->willReturn(DbDump::NAME);
        $this->dbDumpCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->dbDumpCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->dbDumpCommandMock->method('getAliases')
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
                [Deploy::class, $this->deployCommandMock],
                [DbDump::class, $this->dbDumpCommandMock],
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
        $this->assertTrue($this->application->has(Deploy::NAME));
        $this->assertTrue($this->application->has(DbDump::NAME));
        $this->assertTrue($this->application->has(ConfigDump::NAME));
    }
}
