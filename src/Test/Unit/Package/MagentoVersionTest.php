<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;

/**
 * @inheritdoc
 */
class MagentoVersionTest extends TestCase
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @var PackageInterface|MockObject
     */
    private $packageMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->packageMock = $this->getMockBuilder(PackageInterface::class)
            ->getMockForAbstractClass();
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->magentoVersion = new MagentoVersion(
            $this->managerMock,
            new Comparator(),
            new Semver(),
            $this->globalConfigMock
        );
    }

    /**
     * @param string $version
     * @param string $packageVersion
     * @param bool $expected
     * @dataProvider isGreaterOrEqualDataProvider
     */
    public function testIsGreaterOrEqual(string $version, string $packageVersion, bool $expected)
    {
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($packageVersion);

        $this->assertSame(
            $expected,
            $this->magentoVersion->isGreaterOrEqual($version)
        );
    }

    /**
     * @return array
     */
    public function isGreaterOrEqualDataProvider(): array
    {
        return [
            ['2.2', '2.1.9', false],
            ['2.2', '2.2', true],
            ['2.2', '2.2.0', true],
            ['2.2.0', '2.2.0', true],
            ['2.2', '2.2-dev', false],
            ['2.2-dev', '2.2-dev', true],
        ];
    }

    /**
     * Test getting the version number from the installed version of Magento.
     *
     * @throws UndefinedPackageException
     */
    public function testGetVersionFromBasePackage()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2.1');

        $this->assertSame('2.2.1', $this->magentoVersion->getVersion());
        // Test lazy-load.
        $this->assertSame('2.2.1', $this->magentoVersion->getVersion());
    }

    /**
     * Test getting the version number from the installed version of Magento.
     */
    public function testGetVersionFromGit()
    {
        $this->globalConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT],
                [GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT]
            )
            ->willReturn('2.2.1', '2.2.1');
        $this->managerMock->expects($this->never())
            ->method('get');
        $this->packageMock->expects($this->never())
            ->method('getVersion');

        $this->assertSame('2.2.1', $this->magentoVersion->getVersion());
    }

    /**
     * Test the constraint matcher using various Composer-style version constraints.
     *
     * @param string $constraint Composer-style version constraint string
     * @param bool $expected Method name of the assertion to call
     * @dataProvider satisfiesDataProvider
     */
    public function testSatisfies(string $constraint, string $packageVersion, bool $expected)
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->managerMock->expects($this->exactly(1))
            ->method('get')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->exactly(1))
            ->method('getVersion')
            ->willReturn($packageVersion);

        $this->assertSame(
            $expected,
            $this->magentoVersion->satisfies($constraint)
        );
    }

    /**
     * @return array[]
     */
    public function satisfiesDataProvider()
    {
        return [
            ['2.2.1', '2.2.1', true],
            ['2.2.*', '2.2.1', true],
            ['~2.2.0', '2.2.1', true],
            ['2.2.0', '2.2.1', false],
            ['2.1.*', '2.2.1', false],
            ['~2.1.0', '2.2.1', false],
            ['~2.1.0', '2.1.1', true],
            ['~2.2.0', '2.1.1', false],
            ['2.1.*', '2.2', false],
        ];
    }
}
