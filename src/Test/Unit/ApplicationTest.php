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
use Magento\MagentoCloud\Command\PostDeploy;
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
        $deployCommandMock = $this->createMock(Deploy::class);
        $configDumpCommand = $this->createMock(ConfigDump::class);
        $postDeployCommand = $this->createMock(PostDeploy::class);

        $this->mockCommand($buildCommandMock, Build::NAME);
        $this->mockCommand($deployCommandMock, Deploy::NAME);
        $this->mockCommand($configDumpCommand, ConfigDump::NAME);
        $this->mockCommand($postDeployCommand, PostDeploy::NAME);

        $this->containerMock->method('get')
            ->willReturnMap([
                [Composer::class, $this->composerMock],
                [Build::class, $buildCommandMock],
                [Deploy::class, $deployCommandMock],
                [ConfigDump::class, $configDumpCommand],
                [PostDeploy::class, $postDeployCommand],
            ]);
        $this->composerMock->method('getPackage')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('Magento Cloud');
        $this->packageMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('1.0');

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
        $this->assertTrue($this->application->has(ConfigDump::NAME));
        $this->assertTrue($this->application->has(PostDeploy::NAME));
    }

    public function testGetName()
    {
        $this->assertSame(
            'Magento Cloud',
            $this->application->getName()
        );
    }

    public function testGetVersion()
    {
        $this->assertSame(
            '1.0',
            $this->application->getVersion()
        );
    }
}
