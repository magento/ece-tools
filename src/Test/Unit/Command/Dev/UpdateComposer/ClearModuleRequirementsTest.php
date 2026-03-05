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
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ClearModuleRequirementsTest extends TestCase
{
    use PHPMock;

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
    protected function setUp(): void
    {
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_put_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->clearModuleRequirements = new ClearModuleRequirements(
            $this->directoryListMock,
            $this->fileMock
        );
    }

    /**
     * Test generate method with gitignore update.
     *
     * @return void
     * @dataProvider generateGitignoreUpdateDataProvider
     */
    #[DataProvider('generateGitignoreUpdateDataProvider')]
    public function testGenerateGitignoreUpdate($gitignoreContent): void
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/root');

        $gitignorePath = '/root/.gitignore';
        $this->fileMock->expects($this->any())
            ->method('fileGetContents')
            ->with($gitignorePath)
            ->willReturn($gitignoreContent);
        $this->fileMock->expects($this->exactly(1))
            ->method('filePutContents')
            ->with(
                $gitignorePath,
                $this->stringContains('!/clear_module_requirements.php'),
                FILE_APPEND
            );
        $this->assertEquals('clear_module_requirements.php', $this->clearModuleRequirements->generate());
    }

    /**
     * Test generate method with no gitignore update.
     *
     * @return void
     * @dataProvider generateGitignoreNoUpdateDataProvider
     */
    #[DataProvider('generateGitignoreNoUpdateDataProvider')]
    public function testGenerateGitignoreNoUpdate($gitignoreContent): void
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/root');
        $gitignorePath = '/root/.gitignore';
        $this->fileMock->expects($this->any())
            ->method('fileGetContents')
            ->with($gitignorePath)
            ->willReturn($gitignoreContent);
        $this->fileMock->expects($this->never())
            ->method('filePutContents');
        $this->assertEquals('clear_module_requirements.php', $this->clearModuleRequirements->generate());
    }

    /**
     * Data provider for testGenerateGitignoreUpdate.
     *
     * @return array
     */
    public static function generateGitignoreUpdateDataProvider(): array
    {
        return [
            [''],
            ['clear_module_requirements'],
        ];
    }

    /**
     * Data provider for testGenerateGitignoreNoUpdate.
     *
     * @return array
     */
    public static function generateGitignoreNoUpdateDataProvider(): array
    {
        return [
            ['clear_module_requirements.php'],
            ["something\n*\nclear_module_requirements.php"],
        ];
    }
}
