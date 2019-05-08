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
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\GlobalSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var WritableRepositoryInterface|MockObject
     */
    private $localRepositoryMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalSection;

    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
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

        $this->applier = new Applier(
            $this->composerMock,
            $this->shellMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock,
            $this->globalSection
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
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($expectedLog);
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
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName (root/path/to/patch) 1.0.');
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

    public function testApplyPatchAlreadyApplied()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['git apply ' . $path],
                ['git apply --check --reverse ' . $path]
            )
            ->willReturnCallback([$this, 'shellMockReverseCallback']);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName (path/to/patch) 1.0.');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Patch patchName (path/to/patch) was already applied.');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    /**
     * @param string $command
     * @return ProcessInterface
     * @throws ShellException when the command isn't a reverse
     */
    public function shellMockReverseCallback(string $command): ProcessInterface
    {
        if (strpos($command, '--reverse') !== false && strpos($command, '--check') !== false) {
            // Command was the reverse check, it's all good.
            /** @var ProcessInterface|MockObject $result */
            $result = $this->getMockForAbstractClass(ProcessInterface::class);

            return $result;
        }

        // Not a reverse, better throw an exception.
        throw new ShellException('Applying the patch has failed for some reason');
    }

    /**
     * @expectedException \Magento\MagentoCloud\Shell\ShellException
     * @expectedExceptionMessage Checking the reverse of the patch has also failed for some reason
     */
    public function testApplyPatchError()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['git apply ' . $path],
                ['git apply --check --reverse ' . $path]
            )
            ->will($this->returnCallback([$this, 'shellMockErrorCallback']));

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName (path/to/patch) 1.0.');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    public function testApplyPatchErrorDuringInstallationFromGit()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply ' . $path)
            ->will($this->returnCallback([$this, 'shellMockErrorCallback']));
        $this->globalSection->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName (path/to/patch) 1.0.');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                'Patch patchName (path/to/patch) was not applied. (Applying the patch has failed for some reason)'
            );

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    /**
     * @param string $command
     * @throws ShellException
     */
    public function shellMockErrorCallback(string $command)
    {
        if (strpos($command, '--reverse') !== false && strpos($command, '--check') !== false) {
            // Command was the reverse check, still throw an error.
            throw new ShellException('Checking the reverse of the patch has also failed for some reason');
        }

        // Not a reverse, better throw an exception.
        throw new ShellException('Applying the patch has failed for some reason');
    }
}
