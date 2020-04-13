<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ClearModuleRequirements;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ClearModuleRequirementsTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var ClearModuleRequirements
     */
    private $clearModuleRequirements;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->clearModuleRequirements = new ClearModuleRequirements(
            $this->directoryListMock,
            $this->fileMock
        );
    }

    public function testGenerate(): void
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/root');

        $this->fileMock->expects($this->exactly(2))
            ->method('filePutContents')
            ->withConsecutive(
                [
                    '/root/clear_module_requirements.php',
                    file_get_contents(__DIR__ . '/_files/clear_module_requirements.php'),
                ],
                [
                    '/root/.gitignore',
                    $this->stringContains('!/clear_module_requirements.php'),
                    FILE_APPEND
                ]
            );

        $this->clearModuleRequirements->generate([
            'package1' => [
                'type' => 'path',
                'url' => 'repo1/app/code/package1',
            ],
            'package2' => [
                'type' => 'path',
                'url' => 'repo1/app/code/package2',
            ],
            'package3' => [
                'type' => 'composer',
                'url' => 'https://packagist.org/vendor/package3',
            ],
            'package4' => [
                'type' => 'git',
                'url' => 'https://token@github.com/vendor/repo.git',
            ],
            'package5' => [
                'type' => 'git',
                'url' => 'git@github.com:vendor/repo.git',
            ],
            'package6' => [
                'type' => 'path',
                'url' => 'repo2/app/code/package6',
            ],
        ]);
    }
}
