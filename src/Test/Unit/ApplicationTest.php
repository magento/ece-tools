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
use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\Prestart;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Command\PostDeploy;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Container\ContainerInterface;

class ApplicationTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ContainerInterface|Mock
     */
    private $containerMock;

    /**
     * @var Composer|Mock
     */
    private $composerMock;

    /**
     * @var PackageInterface|Mock
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
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);

        /**
         * Command mocks.
         */
        $buildCommandMock = $this->createMock(Build::class);
        $configDumpCommandMock = $this->createMock(ConfigDump::class);
        $cronUnlockCommandMock = $this->createMock(CronUnlock::class);
        $dbDumpCommandMock = $this->createMock(DbDump::class);
        $deployCommandMock = $this->createMock(Deploy::class);
        $postDeployCommandMock = $this->createMock(PostDeploy::class);
        $prestartCommandMock = $this->createMock(Prestart::class);

        $this->mockCommand($buildCommandMock, Build::NAME);
        $this->mockCommand($configDumpCommandMock, ConfigDump::NAME);
        $this->mockCommand($cronUnlockCommandMock, CronUnlock::NAME);
        $this->mockCommand($dbDumpCommandMock, DbDump::NAME);
        $this->mockCommand($deployCommandMock, Deploy::NAME);
        $this->mockCommand($postDeployCommandMock, PostDeploy::NAME);
        $this->mockCommand($prestartCommandMock, Prestart::NAME);

        $this->containerMock->method('get')
            ->willReturnMap([
                [Build::class, $buildCommandMock],
                [Composer::class, $this->composerMock],
                [ConfigDump::class, $configDumpCommandMock],
                [CronUnlock::class, $cronUnlockCommandMock],
                [DbDump::class, $dbDumpCommandMock],
                [Deploy::class, $deployCommandMock],
                [PostDeploy::class, $postDeployCommandMock],
                [Prestart::class, $prestartCommandMock],
            ]);
        $this->composerMock->method('getPackage')
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

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $command
     * @param string $name
     */
    private function mockCommand(\PHPUnit_Framework_MockObject_MockObject $command, string $name)
    {
        $command->method('getName')
            ->willReturn($name);
        $command->method('isEnabled')
            ->willReturn(true);
        $command->method('getDefinition')
            ->willReturn([]);
        $command->method('getAliases')
            ->willReturn([]);
    }

    public function testHasCommand()
    {
        $this->assertTrue($this->application->has(Build::NAME));
        $this->assertTrue($this->application->has(Deploy::NAME));
        $this->assertTrue($this->application->has(DbDump::NAME));
        $this->assertTrue($this->application->has(ConfigDump::NAME));
        $this->assertTrue($this->application->has(Prestart::NAME));
        $this->assertTrue($this->application->has(PostDeploy::NAME));
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
        $this->assertTrue($this->application->has(CronUnlock::NAME));
    }
}
