<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Composer\Repository\LockArrayRepository;
use Magento\MagentoCloud\Shell\Process;
use Magento\MagentoCloud\Shell\ProcessFactory;
use Magento\MagentoCloud\Shell\ProcessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;

/**
 * @inheritDoc
 */
class ProcessFactoryTest extends TestCase
{
    /**
     * @var LockArrayRepository|MockObject
     */
    private $repositoryMock;

    /**
     * @var PackageInterface|MockObject
     */
    private $packageMock;

    /**
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(LockArrayRepository::class);
        $this->packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $this->composerMock = $this->createMock(Composer::class);

        /** @var Locker|MockObject $lockerMock */
        $lockerMock = $this->createMock(Locker::class);
        $lockerMock->expects(self::once())
            ->method('getLockedRepository')
            ->willReturn($this->repositoryMock);

        $this->composerMock->expects(self::once())
            ->method('getLocker')
            ->willReturn($lockerMock);

        $this->processFactory = new ProcessFactory($this->composerMock);
    }

    public function testCreate()
    {
        $this->repositoryMock->expects(self::once())
            ->method('findPackage')
            ->with('symfony/process', '*')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getVersion')
            ->willReturn('4.2.0');

        $params = [
            'command' => 'ls -la',
            'cwd' => '/home/',
            'timeout' => 0,
        ];

        /** @var ProcessInterface|MockObject $processMock */
        $process = $this->processFactory->create($params);

        $this->assertInstanceOf(Process::class, $process);
    }
}
