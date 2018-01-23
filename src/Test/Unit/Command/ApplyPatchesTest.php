<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Composer\Config as ComposerConfig;
use Magento\MagentoCloud\Command\ApplyPatches;
use Magento\MagentoCloud\Patch\Applier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ApplyPatchesTest.
 */
class ApplyPatchesTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var ApplyPatches
     */
    private $command;

    /**
     * @var Composer|Mock
     */
    private $composerMock;

    /**
     * @var Applier|Mock
     */
    private $applierMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ComposerConfig|Mock
     */
    private $composerConfigMock;

    /**
     * @var RootPackageInterface|Mock
     */
    private $composerPackageMock;

    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->applierMock = $this->createMock(Applier::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->composerConfigMock = $this->createMock(ComposerConfig::class);
        $this->composerPackageMock = $this->getMockForAbstractClass(RootPackageInterface::class);

        $this->composerMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->composerConfigMock);
        $this->composerMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->composerPackageMock);

        $this->command = new ApplyPatches(
            $this->composerMock,
            $this->configMock,
            $this->applierMock,
            $this->loggerMock
        );
    }

    public function testExecuteCopyStaticFiles()
    {
        $fileExistsMock = $this->getFunctionMock('Magento\CloudPatches\Command', 'file_exists');
        $fileExistsMock->expects($this->once())
            ->with('/pub/static.php')
            ->willReturn(true);
        $copyMock = $this->getFunctionMock('Magento\CloudPatches\Command', 'copy');
        $copyMock->expects($this->once())
            ->with('/pub/static.php', '/pub/front-static.php')
            ->willReturn(true);

        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Patching started.'],
                ['File static.php was copied.'],
                ['Patching finished.']
            );

        $tester = new CommandTester(
            $this->command
        );

        $tester->execute([]);
    }

    public function testExecuteApplyComposerPatches()
    {
        $this->configMock->expects($this->once())
            ->method('getPatches')
            ->willReturn([
                'package1' => [
                    'patchName1' => [
                        '100' => 'patchPath1',
                    ]
                ],
                'package2' => [
                    'patchName2' => [
                        '101.*' => 'patchPath2',
                    ],
                    'patchName3' => [
                        '102.*' => 'patchPath3',
                    ]
                ],
                'package3' => [
                    'patchName4' => 'patchPath4'
                ],
            ]);
        $this->applierMock->expects($this->exactly(4))
            ->method('apply')
            ->withConsecutive(
                ['patchPath1', 'patchName1', 'package1', '100'],
                ['patchPath2', 'patchName2', 'package2', '101.*'],
                ['patchPath3', 'patchName3', 'package2', '102.*'],
                ['patchPath4', 'patchName4', 'package3', '*']
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Patching started.'],
                ['Patching finished.']
            );

        $tester = new CommandTester(
            $this->command
        );

        $tester->execute([]);
    }

    public function testExecuteApplyHotFixes()
    {
        $hotFixesDir = __DIR__ . '/_files/' . ApplyPatches::HOTFIXES_DIR;

        $this->composerConfigMock->expects($this->exactly(2))
            ->method('get')
            ->with('vendor-dir')
            ->willReturn(__DIR__ . '/_files/vendor');
        $this->applierMock->expects($this->exactly(2))
            ->method('apply')
            ->withConsecutive(
                [$hotFixesDir . '/patch1.patch'],
                [$hotFixesDir . '/patch2.patch']
            );
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Patching started.'],
                ['Applying hot-fixes.'],
                ['Patching finished.']
            );

        $tester = new CommandTester(
            $this->command
        );

        $tester->execute([]);
    }
}
