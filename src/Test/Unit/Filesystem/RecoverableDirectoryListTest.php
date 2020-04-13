<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;

/**
 * @inheritdoc
 */
class RecoverableDirectoryListTest extends TestCase
{
    /**
     * @var RecoverableDirectoryList
     */
    private $recoverableDirectoryList;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @var array
     */
    private $directoryListGetPathReturnMap;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->directoryListGetPathReturnMap = [
            [DirectoryList::DIR_ETC, true, 'app/etc'],
            [DirectoryList::DIR_MEDIA, true, 'pub/media'],
            [DirectoryList::DIR_VIEW_PREPROCESSED, true, 'var/view_preprocessed'],
            [DirectoryList::DIR_STATIC, true, 'pub/static'],
            [DirectoryList::DIR_GENERATED_METADATA, true, 'var/di'],
            [DirectoryList::DIR_GENERATED_CODE, true, 'var/generation']
        ];

        $this->recoverableDirectoryList = new RecoverableDirectoryList(
            $this->environmentMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->magentoVersionMock,
            $this->directoryListMock,
            $this->globalConfigMock
        );
    }

    /**
     * @param bool $isStaticInBuild
     * @param bool $isStaticCleanFiles
     * @param array $expected
     * @dataProvider getListDataProvider22
     */
    public function testGetList22(
        bool $isStaticInBuild,
        bool $isStaticCleanFiles,
        array $expected
    ): void {
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, $isStaticCleanFiles]
            ]);

        $this->directoryListMock->expects($this->exactly(count($expected)))
            ->method('getPath')
            ->willReturnMap($this->directoryListGetPathReturnMap);

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn($isStaticInBuild);

        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturnMap([
                ['2.1.*', false],
            ]);

        $this->assertEquals(
            $expected,
            $this->recoverableDirectoryList->getList()
        );
    }

    /**
     * @return array
     */
    public function getListDataProvider22(): array
    {
        return [
            'symlink and static in build' => [
                'isStaticInBuild' => true,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => StrategyInterface::STRATEGY_SUB_SYMLINK,
                    ],
                ],
            ],
            'static in build and clean' => [
                'isStaticInBuild' => true,
                'isStaticCleanFiles' => false,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => StrategyInterface::STRATEGY_COPY_SUB_FOLDERS,
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => StrategyInterface::STRATEGY_SUB_SYMLINK,
                    ],
                ],
            ],
            'symlink and no static in build' => [
                'isStaticInBuild' => false,
                'isStaticCleanFiles' => false,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param bool $isStaticInBuild
     * @param bool $isGeneratedSymlinkOn
     * @param bool $isStaticCleanFiles
     * @param array $expected
     * @dataProvider getListDataProvider21
     */
    public function testGetList21(
        bool $isGeneratedSymlinkOn,
        bool $isStaticInBuild,
        bool $isStaticCleanFiles,
        array $expected
    ): void {
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK, $isGeneratedSymlinkOn],
                [DeployInterface::VAR_CLEAN_STATIC_FILES, $isStaticCleanFiles]
            ]);

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn($isStaticInBuild);

        $this->directoryListMock->expects($this->exactly(count($expected)))
            ->method('getPath')
            ->willReturnMap($this->directoryListGetPathReturnMap);
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturnMap([
                ['2.1.*', true],
            ]);

        $this->assertEquals(
            $expected,
            $this->recoverableDirectoryList->getList()
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getListDataProvider21(): array
    {
        return [
            'static symlink, no generated symlink, static in build' => [
                'isGeneratedSymlinkOn' => false,
                'isStaticInBuild' => true,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => StrategyInterface::STRATEGY_SUB_SYMLINK,
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                ],
            ],
            'static symlink, no generated symlink, no static in build' => [
                'isGeneratedSymlinkOn' => false,
                'isStaticInBuild' => false,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                ],
            ],
            'static symlink, generated symlink, no static in build' => [
                'isGeneratedSymlinkOn' => true,
                'isStaticInBuild' => false,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => StrategyInterface::STRATEGY_SYMLINK,
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => StrategyInterface::STRATEGY_SYMLINK,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param bool $skipCopyingViewPreprocessed
     * @param bool $isStaticCleanFiles
     * @param array $expected
     * @dataProvider getListSkipCopyingVarViewPreprocessedDataProvider
     */
    public function testGetListSkipCopyingVarViewPreprocessed(
        bool $skipCopyingViewPreprocessed,
        bool $isStaticCleanFiles,
        array $expected
    ): void {
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_SKIP_HTML_MINIFICATION, $skipCopyingViewPreprocessed],
                [DeployInterface::VAR_CLEAN_STATIC_FILES, $isStaticCleanFiles]
            ]);

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_SKIP_HTML_MINIFICATION, $skipCopyingViewPreprocessed]
            ]);

        $this->directoryListMock->expects($this->exactly(count($expected)))
            ->method('getPath')
            ->willReturnMap($this->directoryListGetPathReturnMap);

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);

        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturnMap([
                ['2.1.*', false],
            ]);
        $this->assertEquals(
            $expected,
            $this->recoverableDirectoryList->getList()
        );
    }

    /**
     * @return array
     */
    public function getListSkipCopyingVarViewPreprocessedDataProvider(): array
    {
        return [
            'copying view preprocessed dir' => [
                'skipCopyingViewPreprocessed' => false,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => StrategyInterface::STRATEGY_SUB_SYMLINK,
                    ]
                ],
            ],
            'skip copying view preprocessed dir' => [
                'skipCopyingViewPreprocessed' => true,
                'isStaticCleanFiles' => true,
                'expected' => [
                    [
                        'directory' => 'app/etc',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => StrategyInterface::STRATEGY_SUB_SYMLINK,
                    ]
                ],
            ],
        ];
    }
}
