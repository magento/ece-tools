<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Command\Dev\UpdateComposer\ClearModuleRequirements;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ClearModuleRequirementsTest extends TestCase
{
    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
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

    public function testGenerate()
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
            'repo1' => [
                'branch' => '1.2',
                'repo' => 'https://token@repo1.com',
            ],
            'repo2' => [
                'branch' => '2.2',
                'repo' => 'https://token@repo2.com',
            ],
            'repo3' => [
                'branch' => '2.3',
                'repo' => 'https://token@repo2.com',
                'type' => 'single-package'
            ],
        ]);
    }
}
