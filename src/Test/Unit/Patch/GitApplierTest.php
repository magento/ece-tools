<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Patch\GitApplier;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\GlobalSection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * Class ApplierTest.
 */
class GitApplierTest extends TestCase
{
    /**
     * @var GitApplier
     */
    private $applier;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

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
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->globalSection = $this->createMock(GlobalSection::class);

        $this->applier = new GitApplier(
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
    public function testApply(string $path, $name, string $expectedLog)
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
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

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    /**
     * @return array
     */
    public function applyDataProvider(): array
    {
        return [
            ['path/to/patch', 'patchName', 'Applying patch patchName (path/to/patch).'],
            ['path/to/patch2', null, 'Applying patch path/to/patch2.'],
        ];
    }

    public function testApplyPathNotExists()
    {
        $path = 'path/to/patch';
        $name = 'patchName';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(false);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply root/' . $path);
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Applying patch patchName (root/path/to/patch).'],
                ['Done.']
            );
        $this->directoryListMock->expects($this->once())
            ->method('getPatches')
            ->willReturn('root');

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    public function testApplyPatchAlreadyApplied()
    {
        $path = 'path/to/patch';
        $name = 'patchName';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['git apply ' . $path],
                ['git apply --check --reverse ' . $path]
            )
            ->willReturnCallback([$this, 'shellMockReverseCallback']);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Applying patch patchName (path/to/patch).'],
                ['Done.']
            );
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with("Patch patchName (path/to/patch) was already applied.");

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    /**
     * @param string $command
     * @throws RuntimeException when the command isn't a reverse
     */
    public function shellMockReverseCallback(string $command)
    {
        if (strpos($command, '--reverse') !== false && strpos($command, '--check') !== false) {
            // Command was the reverse check, it's all good.
            return;
        }

        // Not a reverse, better throw an exception.
        throw new \RuntimeException('Applying the patch has failed for some reason');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Applying the patch has failed for some reason
     */
    public function testApplyPatchError()
    {
        $path = 'path/to/patch';
        $name = 'patchName';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['git apply ' . $path],
                ['git apply --check --reverse ' . $path]
            )
            ->will($this->returnCallback([$this, 'shellMockErrorCallback']));

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName (path/to/patch).');

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    public function testApplyPatchErrorDuringInstallationFromGit()
    {
        $path = 'path/to/patch';
        $name = 'patchName';

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($path)
            ->willReturn(true);
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
            ->with('Applying patch patchName (path/to/patch).');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Patch patchName (path/to/patch) wasn\'t applied.');

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    /**
     * @param string $command
     * @throws RuntimeException
     */
    public function shellMockErrorCallback(string $command)
    {
        if (strpos($command, '--reverse') !== false && strpos($command, '--check') !== false) {
            // Command was the reverse check, still throw an error.
            throw new \RuntimeException('Checking the reverse of the patch has also failed for some reason');
        }

        // Not a reverse, better throw an exception.
        throw new \RuntimeException('Applying the patch has failed for some reason');
    }
}
