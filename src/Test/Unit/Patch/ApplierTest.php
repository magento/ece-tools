<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Patch\Applier;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * Class ApplierTest.
 */
class ApplierTest extends TestCase
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var Composer|Mock
     */
    private $composerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var WritableRepositoryInterface|Mock
     */
    private $localRepositoryMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->localRepositoryMock = $this->getMockForAbstractClass(WritableRepositoryInterface::class);
        $repositoryManagerMock = $this->createMock(RepositoryManager::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->willReturn($this->localRepositoryMock);
        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManagerMock);

        $this->applier = new Applier(
            $this->composerMock,
            $this->shellMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock
        );
    }

    /**
     * @param string $path
     * @param string|null $name
     * @param string|null $packageName
     * @param string|null $constraint
     * @param string $expectedLog
     * @dataProvider applyDataProvider
     */
    public function testApply(string $path, $name, $packageName, $constraint, string $expectedLog)
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->any())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply ' . $path);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$expectedLog],
                ['Done.']
            );
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    /**
     * @return array
     */
    public function applyDataProvider(): array
    {
        return [
            ['path/to/patch', 'patchName', 'packageName', '1.0', 'Applying patch patchName (path/to/patch) 1.0.'],
            ['path/to/patch2', null, null, null, 'Applying patch path/to/patch2.'],
        ];
    }

    public function testApplyPathNotExists()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(false);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply root/' . $path);
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Applying patch patchName (path/to/patch) 1.0.'],
                ['Done.']
            );
        $this->directoryListMock->expects($this->once())
            ->method('getPatches')
            ->willReturn('root');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    public function testApplyPathNotExistsAndNotMatchedConstraints()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(false);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn(null);
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }
}
