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
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildCommandMock;

    /**
     * @var Deploy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deployCommandMock;

    /**
     * @var ConfigDump|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configDumpCommand;

    /**
     * @var PostDeploy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $postDeployCommand;

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
        $this->configDumpCommand = $this->createMock(ConfigDump::class);
        $this->postDeployCommand = $this->createMock(PostDeploy::class);

        /**
         * Build.
         */
        $this->buildCommandMock->method('getName')
            ->willReturn(Build::NAME);
        $this->buildCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->buildCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->buildCommandMock->method('getAliases')
            ->willReturn([]);

        /**
         * Deploy.
         */
        $this->deployCommandMock->method('getName')
            ->willReturn(Deploy::NAME);
        $this->deployCommandMock->method('isEnabled')
            ->willReturn(true);
        $this->deployCommandMock->method('getDefinition')
            ->willReturn([]);
        $this->deployCommandMock->method('getAliases')
            ->willReturn([]);

        /**
         * Config dump.
         */
        $this->configDumpCommand->method('getName')
            ->willReturn(ConfigDump::NAME);
        $this->configDumpCommand->method('isEnabled')
            ->willReturn(true);
        $this->configDumpCommand->method('getDefinition')
            ->willReturn([]);
        $this->configDumpCommand->method('getAliases')
            ->willReturn([]);

        /**
         * Post deploy.
         */
        $this->postDeployCommand->method('getName')
            ->willReturn(PostDeploy::NAME);
        $this->postDeployCommand->method('isEnabled')
            ->willReturn(true);
        $this->postDeployCommand->method('getDefinition')
            ->willReturn([]);
        $this->postDeployCommand->method('getAliases')
            ->willReturn([]);

        $this->containerMock->method('get')
            ->willReturnMap([
                [Composer::class, $this->composerMock],
                [Build::class, $this->buildCommandMock],
                [Deploy::class, $this->deployCommandMock],
                [ConfigDump::class, $this->configDumpCommand],
                [PostDeploy::class, $this->postDeployCommand],
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
