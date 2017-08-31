<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Composer\Composer;
use Composer\Factory;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\ComponentInfo;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ComponentInfoTest extends TestCase
{
    /**
     * @var Factory|Mock
     */
    private $composerFactoryMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var Composer|Mock
     */
    private $composerMock;

    /**
     * @var RepositoryInterface|Mock
     */
    private $composerRepositoryMock;

    /**
     * @var ComponentInfo
     */
    private $componentInfo;

    protected function setUp()
    {
        $this->composerFactoryMock = $this->createMock(Factory::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->composerRepositoryMock = $this->getMockBuilder(RepositoryInterface::class)
            ->getMockForAbstractClass();
        $lockerMock = $this->createMock(Locker::class);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/path/to/root');
        $this->composerFactoryMock->expects($this->once())
            ->method('createComposer')
            ->with($this->anything(), '/path/to/root/composer.json')
            ->willReturn($this->composerMock);
        $this->composerMock->expects($this->once())
            ->method('getLocker')
            ->willReturn($lockerMock);
        $lockerMock->expects($this->once())
            ->method('getLockedRepository')
            ->willReturn($this->composerRepositoryMock);

        $this->componentInfo = new ComponentInfo(
            $this->composerFactoryMock,
            $this->directoryListMock
        );
    }

    public function testGet()
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

        $this->composerRepositoryMock->expects($this->exactly(2))
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
            $this->componentInfo->get()
        );
    }

    public function testGetWithNotExistPackage()
    {
        $packageOneMock = $this->getMockBuilder(PackageInterface::class)
            ->getMockForAbstractClass();
        $packageOneMock->expects($this->once())
            ->method('getPrettyName')
            ->willReturn('vendor/package1');
        $packageOneMock->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn('v1.0.0');

        $this->composerRepositoryMock->expects($this->exactly(2))
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
            $this->componentInfo->get(['vendor/package1', 'vendor/not-exists-package'])
        );
    }
}
