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
                    $this->stringContains('foreach ([\'repo1\', \'repo2\'] as $repoName)')
                ],
                [
                    '/root/.gitignore',
                    $this->stringContains('!/clear_module_requirements.php'),
                    FILE_APPEND
                ]
            );

        $this->clearModuleRequirements->generate(['repo1', 'repo2']);
    }
}
