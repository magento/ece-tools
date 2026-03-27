<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputDefinition;
use Throwable;

/**
 * @SuppressWarnings(PHPMD\CouplingBetweenObjects)
 */
#[AllowMockObjectsWithoutExpectations]
class ApplicationTest extends TestCase
{
    /**
     * @var Application
     */
    private Application $application;

    /**
     * @var ContainerInterface&MockObject
     */
    private ContainerInterface $containerMock;

    /**
     * @var Composer&MockObject
     */
    private Composer $composerMock;

    /**
     * @var RootPackageInterface&MockObject
     */
    private RootPackageInterface $packageMock;

    /**
     * @var InputDefinition&MockObject
     */
    private InputDefinition $inputDefinitionMock;

    /**
     * @var string
     */
    private string $applicationVersion;

    /**
     * @var string
     */
    private string $applicationName;

    /**
     * Classes passed to application.
     *
     * @var array<string, class-string>
     */
    private array $classMap = [
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
        // Load version and name from composer.json
        $this->loadComposerMetadata();

        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->packageMock = $this->createMock(RootPackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->inputDefinitionMock = $this->createMock(InputDefinition::class);

        $map = [
            [Composer::class, [], $this->composerMock],
        ];

        foreach ($this->classMap as $name => $className) {
            $mock = $this->createStub($className);
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
        $this->composerMock->method('getPackage')
            ->willReturn($this->packageMock);
        $this->packageMock->method('getPrettyName')
            ->willReturn($this->applicationName);
        $this->packageMock->method('getPrettyVersion')
            ->willReturn($this->applicationVersion);

        $this->application = new Application(
            $this->containerMock
        );
    }

    /**
     * Test to verify all expected command classes are registered in the application.
     *
     * @return void
     */
    public function testHasCommand(): void
    {
        foreach (array_keys($this->classMap) as $name) {
            $this->assertTrue(
                $this->application->has($name)
            );
        }
    }

    /**
     * Test to verify getName() returns the expected name from composer.json
     * or runtime metadata.
     *
     * @return void
     */
    public function testGetName(): void
    {
        $this->assertSame(
            $this->applicationName,
            $this->application->getName()
        );
    }

    /**
     * Test to verify getVersion() returns the expected application version
     * from composer.json or runtime metadata.
     *
     * @return void
     */
    public function testGetVersion(): void
    {
        $this->assertSame(
            $this->applicationVersion,
            $this->application->getVersion()
        );
    }

    /**
     * Test to verify getLongVersion() returns "name version"
     * including application name and version.
     *
     * @return void
     */
    public function testGetLongVersion(): void
    {
        $expectedVersion = sprintf('%s <info>%s</info>', $this->applicationName, $this->applicationVersion);
        $this->assertSame(
            $expectedVersion,
            $this->application->getLongVersion()
        );
    }

    /**
     * Test to verify getContainer() returns the same container instance used during initialization.
     *
     * @return void
     */
    public function testGetContainer(): void
    {
        $this->assertSame(
            $this->containerMock,
            $this->application->getContainer()
        );
    }

    /**
     * Load composer.json to set application name and version,
     * falling back to defaults if unavailable.
     *
     * @return void
     */
    private function loadComposerMetadata(): void
    {
        $this->applicationName    = 'magento/ece-tools';
        $this->applicationVersion = '0.0.0';

        $repoRoot         = dirname(__DIR__, 3);
        $composerJsonPath = $repoRoot . '/composer.json';

        if (!is_file($composerJsonPath) || !is_readable($composerJsonPath)) {
            return;
        }

        try {
            $composerJson = json_decode(
                (string) file_get_contents($composerJsonPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (is_array($composerJson)) {
                $this->applicationName = $composerJson['name'] ?? $this->applicationName;
                $this->applicationVersion = $composerJson['version'] ?? $this->applicationVersion;
            }
        } catch (Throwable $e) {
            // Keep defaults
        }
    }
}
