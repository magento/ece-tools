<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var PackageInterface|MockObject
     */
    private $rootPackageMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(Manager::class);
        $this->packageMock = $this->createMock(PackageInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->rootPackageMock = $this->createMock(RootPackageInterface::class);

        $this->composerMock->method('getPackage')
            ->willReturn($this->rootPackageMock);

        $this->magentoVersion = new MagentoVersion(
            $this->managerMock,
            new Comparator(),
            new Semver(),
            $this->globalConfigMock,
            $this->composerMock
        );
    }

    /**
     * Test isGreaterOrEqual method.
     *
     * @param string $version
     * @param string $packageVersion
     * @param bool $expected
     * @dataProvider isGreaterOrEqualDataProvider
     */
    #[DataProvider('isGreaterOrEqualDataProvider')]
    public function testIsGreaterOrEqual(string $version, string $packageVersion, bool $expected): void
    {
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(true);
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects(self::once())
            ->method('getVersion')
            ->willReturn($packageVersion);

        self::assertSame(
            $expected,
            $this->magentoVersion->isGreaterOrEqual($version)
        );
    }

    /**
     * @return array
     */
    public static function isGreaterOrEqualDataProvider(): array
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
    public function testGetVersionFromBasePackage(): void
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(true);
        $this->managerMock->method('get')
            ->with('magento/magento2-base')
            ->willReturn($this->packageMock);
        $this->packageMock->expects(self::once())
            ->method('getVersion')
            ->willReturn('2.2.1');

        self::assertSame('2.2.1', $this->magentoVersion->getVersion());
        // Test lazy-load.
        self::assertSame('2.2.1', $this->magentoVersion->getVersion());
    }

    /**
     * Test getting the version number from the installed version of Magento.
     */
    public function testGetVersionFromGit(): void
    {
        $series = [
            [[GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT], '2.2.1'],
            [[GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT], '2.2.1'],
        ];
        $this->globalConfigMock->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(function (...$args) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->managerMock->expects(self::never())
            ->method('get');
        $this->packageMock->expects(self::never())
            ->method('getVersion');

        self::assertSame('2.2.1', $this->magentoVersion->getVersion());
    }

    /**
     * Test the constraint matcher using various Composer-style version constraints.
     *
     * @param string $constraint Composer-style version constraint string
     * @param bool $expected Method name of the assertion to call
     * @dataProvider satisfiesDataProvider
     */
    #[DataProvider('satisfiesDataProvider')]
    public function testSatisfies(string $constraint, string $packageVersion, bool $expected): void
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->managerMock->expects(self::once())
            ->method('get')
            ->willReturn($this->packageMock);
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(true);
        $this->packageMock->expects(self::once())
            ->method('getVersion')
            ->willReturn($packageVersion);

        self::assertSame(
            $expected,
            $this->magentoVersion->satisfies($constraint)
        );
    }

    /**
     * Test the constraint matcher using various Composer-style version constraints.
     *
     * @return array[]
     */
    public static function satisfiesDataProvider(): array
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

    /**
     * Test getting the version number from the installed version of Magento.
     *
     * @return void
     * @throws UndefinedPackageException
     */
    public function testWithComposerVersion(): void
    {
        $this->rootPackageMock->method('getPrettyVersion')
            ->willReturn('2.4.2');

        self::assertSame('2.4.2', $this->magentoVersion->getVersion());
        // Test lazy-load.
        self::assertSame('2.4.2', $this->magentoVersion->getVersion());
    }

    /**
     * Test getVersion method when the version cannot be resolved.
     *
     * @throws UndefinedPackageException
     */
    public function testCannotResolve(): void
    {
        $this->expectException(UndefinedPackageException::class);
        $this->expectExceptionMessage('Magento version cannot be resolved');

        $this->magentoVersion->getVersion();
    }

    /**
     * Test getVersion method when an exception is thrown.
     *
     * @throws UndefinedPackageException
     */
    public function testGetVersionWithException(): void
    {
        $this->expectException(UndefinedPackageException::class);
        $this->expectExceptionMessage('Some error');

        $this->globalConfigMock->method('get')
            ->willThrowException(new ConfigException('Some error'));

        $this->magentoVersion->getVersion();
    }

    /**
     * Test isGitInstallation method.
     *
     * @throws ConfigException
     */
    public function testIsGitInstallation(): void
    {
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(true);

        self::assertFalse($this->magentoVersion->isGitInstallation());
    }

    /**
     * Test isGitInstallation method when the installation is detected via environment variable.
     *
     * @throws ConfigException
     */
    public function testIsGitInstallationEnvVariable(): void
    {
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(false);
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn('2.4.0');

        self::assertTrue($this->magentoVersion->isGitInstallation());
    }

    /**
     * Test isGitInstallation method when the installation is detected via Composer.
     *
     * @throws ConfigException
     */
    public function testIsGitInstallationComposer(): void
    {
        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(false);
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->rootPackageMock->method('getPrettyVersion')
            ->willReturn('2.4.2');

        self::assertTrue($this->magentoVersion->isGitInstallation());
    }

    /**
     * Test isGitInstallation method when an exception is thrown.
     *
     * @throws ConfigException
     */
    public function testIsGitInstallationException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Version cannot be determined');

        $this->managerMock->method('has')
            ->with('magento/magento2-base')
            ->willReturn(false);
        $this->globalConfigMock->expects(self::once())
            ->method('get')
            ->with(GlobalConfig::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);

        self::assertTrue($this->magentoVersion->isGitInstallation());
    }
}
