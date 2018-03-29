<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Package;

use Composer\Composer;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Package\Link;
use Magento\MagentoCloud\Package\Manager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var Composer|Mock
     */
    private $composerMock;

    /**
     * @var RepositoryInterface|Mock
     */
    private $repositoryMock;

    /**
     * @var PackageInterface|Mock
     */
    private $packageMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->repositoryMock = $this->getMockBuilder(RepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);
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

    public function testGetPrettyInfo()
    {
        $packageOneMock = $this->getMockBuilder(PackageInterface::class)
            ->getMockForAbstractClass();
        $packageOneMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('magento/ece-tools');
        $packageOneMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('v1.0.0');

        $packageTwoMock = $this->getMockBuilder(PackageInterface::class)
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

    public function testGetPrettyInfoWithNotExistPackage()
    {
        $packageOneMock = $this->getMockBuilder(PackageInterface::class)
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

    public function testGet()
    {
        $this->repositoryMock->method('findPackage')
            ->with('some_package', '*')
            ->willReturn($this->packageMock);

        $this->assertInstanceOf(
            PackageInterface::class,
            $this->packageManager->get('some_package')
        );
    }

    /**
     * @expectedExceptionMessage Package some_package:* was not found
     * @expectedException \Exception
     */
    public function testGetWithException()
    {
        $this->repositoryMock->method('findPackage')
            ->with('some_package', '*')
            ->willReturn(null);

        $this->packageManager->get('some_package');
    }

    public function testHas()
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

        $this->assertSame(true, $this->packageManager->has('some_package'));
        $this->assertSame(false, $this->packageManager->has('some_package', '0.1'));
    }

    public function testGetRequiredPackageNames()
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
