<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Dev;

use Magento\MagentoCloud\Command\Dev\UpdateComposer;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class UpdateComposerTest extends TestCase
{
    /**
     * @var UpdateComposer
     */
    private $updateComposerCommand;

    /**
     * @var UpdateComposer\ComposerGenerator|MockObject
     */
    private $composerGeneratorMock;

    /**
     * @var UpdateComposer\ClearModuleRequirements|MockObject
     */
    private $clearModuleRequirementsMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalSectionMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->composerGeneratorMock = $this->createMock(UpdateComposer\ComposerGenerator::class);
        $this->clearModuleRequirementsMock = $this->createMock(UpdateComposer\ClearModuleRequirements::class);
        $this->shellMock = $this->createMock(ShellInterface::class);
        $this->globalSectionMock = $this->createMock(GlobalSection::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->updateComposerCommand = new UpdateComposer(
            $this->composerGeneratorMock,
            $this->clearModuleRequirementsMock,
            $this->shellMock,
            $this->globalSectionMock,
            $this->fileListMock,
            $this->fileMock
        );
    }

    public function testExecute(): void
    {
        $gitOptions = [
            'clear_magento_module_requirements' => true,
            'repositories' => [
                'repo1' => [
                    'repo' => 'path_to_repo1',
                    'branch' => '1.0.0'
                ],
                'repo2' => [
                    'repo' => 'path_to_repo2',
                    'branch' => '1.0.0'
                ],
            ]
        ];
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOY_FROM_GIT_OPTIONS)
            ->willReturn($gitOptions);
        $this->composerGeneratorMock->expects($this->once())
            ->method('getInstallFromGitScripts')
            ->with($gitOptions['repositories'])
            ->willReturn([
                'script1',
                'script2',
                'script3',
            ]);
        $this->composerGeneratorMock->expects($this->once())
            ->method('getFrameworkPreparationScript')
            ->with(array_keys($gitOptions['repositories']))
            ->willReturn([
                'script4',
                'script5',
            ]);
        $this->composerGeneratorMock->expects($this->once())
            ->method('generate')
            ->with($gitOptions['repositories'])
            ->willReturn([
                'name' => 'magento/cloud',
                'repositories' => [
                    'vendor1/package1' => [
                        'type' => 'path',
                        'url' => 'repo1'
                    ],
                    'vendor2/package2' => [
                        'type' => 'path',
                        'url' => 'repo2'
                    ],
                ],
            ]);
        $this->shellMock->expects($this->exactly(5))
            ->method('execute')
            ->withConsecutive(
                ['script2'],
                ['script3'],
                ['script4'],
                ['script5'],
                ['composer update --ansi --no-interaction']
            );
        $this->fileListMock->expects($this->once())
            ->method('getMagentoComposer')
            ->willReturn('/magento_root/composer.json');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('/magento_root/composer.json');

        $tester = new CommandTester(
            $this->updateComposerCommand
        );
        $tester->execute([]);
    }
}
