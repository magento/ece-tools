<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputDefinition;

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
     * @var RootPackageInterface|MockObject
     */
    private $packageMock;

    /**
     * @var InputDefinition|MockObject
     */
    private $inputDefinitionMock;

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
        Command\Dev\GenerateSchemaError::NAME => Command\Dev\GenerateSchemaError::class,
        Command\Wizard\ScdOnBuild::NAME => Command\Wizard\ScdOnBuild::class,
        Command\Wizard\ScdOnDeploy::NAME => Command\Wizard\ScdOnDeploy::class,
        Command\Wizard\ScdOnDemand::NAME => Command\Wizard\ScdOnDemand::class,
        Command\ModuleRefresh::NAME => Command\ModuleRefresh::class,
        Command\Wizard\IdealState::NAME => Command\Wizard\IdealState::class,
        Command\Wizard\MasterSlave::NAME => Command\Wizard\MasterSlave::class,
        Command\Wizard\SplitDbState::NAME => Command\Wizard\SplitDbState::class,
        Command\CronKill::NAME => Command\CronKill::class,
        Command\CronEnable::NAME => Command\CronEnable::class,
        Command\CronDisable::NAME => Command\CronDisable::class,
        Command\ConfigShow::NAME => Command\ConfigShow::class,
        Command\ConfigCreate::NAME => Command\ConfigCreate::class,
        Command\ConfigUpdate::NAME => Command\ConfigUpdate::class,
        Command\ConfigValidate::NAME => Command\ConfigValidate::class,
        Command\RunCommand::NAME => Command\RunCommand::class,
        Command\GenerateSchema::NAME => Command\GenerateSchema::class,
        Command\ErrorShow::NAME => Command\ErrorShow::class,
    ];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->packageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->inputDefinitionMock = $this->createMock(InputDefinition::class);

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
                ->willReturn($this->inputDefinitionMock);
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

    public function testHasCommand(): void
    {
        foreach (array_keys($this->classMap) as $name) {
            $this->assertTrue(
                $this->application->has($name)
            );
        }
    }

    public function testGetName(): void
    {
        $this->assertSame(
            $this->applicationName,
            $this->application->getName()
        );
    }

    public function testGetVersion(): void
    {
        $this->assertSame(
            $this->applicationVersion,
            $this->application->getVersion()
        );
    }

    public function testGetContainer(): void
    {
        $this->application->getContainer();
    }
}
