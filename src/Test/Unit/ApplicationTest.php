<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplicationTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var PackageInterface|MockObject
     */
    private $packageMock;

    /**
     * @var string
     */
    private $applicationVersion = '1.0';

    /**
     * @var string
     */
    private $applicationName = 'Magento Cloud Tools';

    /**
     * Classes passed to application.
     *
     * @var array
     */
    private $classMap = [
        Command\Build::NAME => Command\Build::class,
        Command\Build\Generate::NAME => Command\Build\Generate::class,
        Command\Build\Transfer::NAME => Command\Build\Transfer::class,
        Command\ConfigDump::NAME => Command\ConfigDump::class,
        Command\CronUnlock::NAME => Command\CronUnlock::class,
        Command\DbDump::NAME => Command\DbDump::class,
        Command\Deploy::NAME => Command\Deploy::class,
        Command\PostDeploy::NAME => Command\PostDeploy::class,
        Command\BackupRestore::NAME => Command\BackupRestore::class,
        Command\BackupList::NAME => Command\BackupList::class,
        Command\ApplyPatches::NAME => Command\ApplyPatches::class,
        Command\Dev\UpdateComposer::NAME => Command\Dev\UpdateComposer::class,
        Command\Wizard\ScdOnBuild::NAME => Command\Wizard\ScdOnBuild::class,
        Command\Wizard\ScdOnDeploy::NAME => Command\Wizard\ScdOnDeploy::class,
        Command\Wizard\ScdOnDemand::NAME => Command\Wizard\ScdOnDemand::class,
        Command\ModuleRefresh::NAME => Command\ModuleRefresh::class,
        Command\Wizard\IdealState::NAME => Command\Wizard\IdealState::class,
        Command\Wizard\MasterSlave::NAME => Command\Wizard\MasterSlave::class,
        Command\Docker\Build::NAME => Command\Docker\Build::class,
        Command\Docker\GenerateDist::NAME => Command\Docker\GenerateDist::class,
        Command\CronKill::NAME => Command\CronKill::class,
        Command\ConfigShow::NAME => Command\ConfigShow::class,
    ];

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);

        $map = [
            [Composer::class, [], $this->composerMock],
        ];

        foreach ($this->classMap as $name => $className) {
            $mock = $this->createMock($className);
            $mock->method('getName')
                ->willReturn($name);
            $mock->method('isEnabled')
                ->willReturn(true);
            $mock->method('getDefinition')
                ->willReturn([]);
            $mock->method('getAliases')
                ->willReturn([]);

            $map[] = [$className, [], $mock];
        }

        $this->containerMock->method('create')
            ->willReturnMap($map);
        $this->composerMock->expects($this->any())
            ->method('getPackage')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn($this->applicationName);
        $this->packageMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($this->applicationVersion);

        $this->application = new Application(
            $this->containerMock
        );
    }

    public function testHasCommand()
    {
        foreach (array_keys($this->classMap) as $name) {
            $this->assertTrue(
                $this->application->has($name)
            );
        }
    }

    public function testGetName()
    {
        $this->assertSame(
            $this->applicationName,
            $this->application->getName()
        );
    }

    public function testGetVersion()
    {
        $this->assertSame(
            $this->applicationVersion,
            $this->application->getVersion()
        );
    }

    public function testGetContainer()
    {
        $this->application->getContainer();
    }
}
