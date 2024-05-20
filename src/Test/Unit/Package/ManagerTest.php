<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Composer;
use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;
use Composer\Repository\LockArrayRepository;
use Composer\Package\Link;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    private $packageManager;

    /**
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var LockArrayRepository|MockObject
     */
    private $repositoryMock;

    /**
     * @var RootPackageInterface|MockObject
     */
    private $packageMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->repositoryMock = $this->createMock(LockArrayRepository::class);
        $this->packageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $lockerMock = $this->createMock(Locker::class);

        $this->composerMock->expects($this->once())
            ->method('getLocker')
            ->willReturn($lockerMock);
        $lockerMock->expects($this->once())
            ->method('getLockedRepository')
            ->willReturn($this->repositoryMock);

        $this->packageManager = new Manager(
            $this->composerMock
        );
    }

    public function testGetPrettyInfo(): void
    {
        $packageOneMock = $this->getMockBuilder(RootPackageInterface::class)
            ->getMockForAbstractClass();
        $packageOneMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('magento/ece-tools');
        $packageOneMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('v1.0.0');

        $packageTwoMock = $this->getMockBuilder(RootPackageInterface::class)
            ->getMockForAbstractClass();
        $packageTwoMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('magento/magento2-base');
        $packageTwoMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('v2.0.0');

        $this->repositoryMock->expects($this->exactly(2))
            ->method('findPackage')
            ->withConsecutive(
                ['magento/ece-tools', '*'],
                ['magento/magento2-base', '*']
            )
            ->willReturnOnConsecutiveCalls(
                $packageOneMock,
                $packageTwoMock
            );

        $this->assertEquals(
            '(magento/ece-tools version: v1.0.0, magento/magento2-base version: v2.0.0)',
            $this->packageManager->getPrettyInfo()
        );
    }

    public function testGetPrettyInfoWithNotExistPackage(): void
    {
        $packageOneMock = $this->getMockBuilder(RootPackageInterface::class)
            ->getMockForAbstractClass();
        $packageOneMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('vendor/package1');
        $packageOneMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('v1.0.0');

        $this->repositoryMock->expects($this->exactly(2))
            ->method('findPackage')
            ->withConsecutive(
                ['vendor/package1', '*'],
                ['vendor/not-exists-package', '*']
            )
            ->willReturnOnConsecutiveCalls(
                $packageOneMock,
                null
            );

        $this->assertEquals(
            '(vendor/package1 version: v1.0.0)',
            $this->packageManager->getPrettyInfo(['vendor/package1', 'vendor/not-exists-package'])
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGet(): void
    {
        $this->repositoryMock->method('findPackage')
            ->with('some_package', '*')
            ->willReturn($this->packageMock);

        $this->packageManager->get('some_package');
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetWithException(): void
    {
        $this->expectException(UndefinedPackageException::class);
        $this->expectExceptionMessage('Package some_package:* was not found');
        $this->expectExceptionCode(Error::BUILD_COMPOSER_PACKAGE_NOT_FOUND);

        $this->repositoryMock->method('findPackage')
            ->with('some_package', '*')
            ->willReturn(null);

        $this->packageManager->get('some_package');
    }

    public function testHas(): void
    {
        $this->repositoryMock->method('findPackage')
            ->withConsecutive(
                ['some_package', '*'],
                ['some_package', '0.1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->packageMock,
                null
            );

        $this->assertTrue($this->packageManager->has('some_package'));
        $this->assertFalse($this->packageManager->has('some_package', '0.1'));
    }

    public function testGetRequiredPackageNames(): void
    {
        $linkMock = $this->createMock(Link::class);

        $linkMock->expects($this->once())
            ->method('getTarget')
            ->willReturn('some/random-package');
        $this->composerMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->packageMock);
        $this->packageMock->expects($this->once())
            ->method('getRequires')
            ->willReturn([$linkMock]);

        $this->packageManager->getRequiredPackageNames();
    }
}
