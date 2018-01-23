<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Composer\Composer;
use Composer\Config;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
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
    use \phpmock\phpunit\PHPMock;

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
     * @var Config|Mock
     */
    private $composerConfigMock;

    protected function setUp()
    {
        $this->markTestIncomplete();
        $this->composerMock = $this->createMock(Composer::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->localRepositoryMock = $this->getMockForAbstractClass(WritableRepositoryInterface::class);
        $this->composerConfigMock = $this->createMock(Config::class);
        $repositoryManagerMock = $this->createMock(RepositoryManager::class);

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->willReturn($this->localRepositoryMock);
        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManagerMock);
        $this->composerMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->composerConfigMock);

        $this->applier = new Applier(
            $this->composerMock,
            $this->shellMock,
            $this->loggerMock
        );
    }

    public function testApply()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $fileExistsMock = $this->getFunctionMock('Magento\CloudPatches\Patch', 'file_exists');
        $fileExistsMock->expects($this->once())
            ->with($path)
            ->willReturn(true);

        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply ' . $path);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Applying patch patchName 1.0.'],
                ['Done.']
            );
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->composerConfigMock->expects($this->never())
            ->method('get');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    public function testApplyPathNotExists()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $execMock = $this->getFunctionMock('Magento\CloudPatches\Patch', 'file_exists');
        $execMock->expects($this->once())
            ->with($path)
            ->willReturn(false);

        $this->composerConfigMock->expects($this->once())
            ->method('get')
            ->with('vendor-dir')
            ->willReturn('/root');
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('git apply /root/magento/ece-patches/' . $path);
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Applying patch patchName 1.0.'],
                ['Done.']
            );

        $this->applier->apply($path, $name, $packageName, $constraint);
    }

    public function testApplyPathNotExistsAndNotMatchedConstraints()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $execMock = $this->getFunctionMock('Magento\CloudPatches\Patch', 'file_exists');
        $execMock->expects($this->once())
            ->with($path)
            ->willReturn(false);

        $this->composerConfigMock->expects($this->once())
            ->method('get')
            ->with('vendor-dir')
            ->willReturn('/root');
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn(null);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Constraint packageName 1.0 was not found.');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patch patchName 1.0.');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->applier->apply($path, $name, $packageName, $constraint);
    }
}
