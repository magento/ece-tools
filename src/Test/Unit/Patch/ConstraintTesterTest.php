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
use Magento\MagentoCloud\Patch\ConstraintTester;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\GlobalSection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 *  Tests the Constraint Tester for patches.
 */
class ConstraintTesterTest extends TestCase
{

    /**
     * @var ConstraintTester
     */
    private $constraintTester;

    /**
     * @var Composer|Mock
     */
    private $composerMock;

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

    /**
     * @var GlobalSection|Mock
     */
    private $globalSection;

    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->localRepositoryMock = $this->getMockForAbstractClass(WritableRepositoryInterface::class);
        $repositoryManagerMock = $this->createMock(RepositoryManager::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->globalSection = $this->createMock(GlobalSection::class);

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->willReturn($this->localRepositoryMock);
        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManagerMock);

        $this->constraintTester = new ConstraintTester(
            $this->composerMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock,
            $this->globalSection
        );
    }

    /**
     * @param string $path
     * @param string|null $packageName
     * @param string|null $constraint
     * @param string $expectedLog
     * @dataProvider constraintDataProvider
     */
    public function testTestConstraint(string $path, $packageName, $constraint, string $expectedLog)
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->any())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $returnValue = $this->constraintTester->testConstraint($path, $packageName, $constraint);
        $this->assertEquals($expectedLog, $returnValue);
    }

    /**
     * @return array
     */
    public function constraintDataProvider(): array
    {
        return [
            ['path/to/patch', 'packageName', '1.0', 'path/to/patch'],
            ['path/to/patch2', null, null, 'path/to/patch2'],
        ];
    }

    public function testApplyPathNotExists()
    {
        $path = 'path/to/patch';
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
        $this->directoryListMock->expects($this->once())
            ->method('getPatches')
            ->willReturn('root');

        $returnValue = $this->constraintTester->testConstraint($path, $packageName, $constraint);
        $this->assertEquals('root/path/to/patch', $returnValue);
    }

    public function testApplyPathNotExistsAndNotMatchedConstraints()
    {
        $path = 'path/to/patch';
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

        $returnValue = $this->constraintTester->testConstraint($path, $packageName, $constraint);
        $this->assertNull($returnValue);
    }
}
