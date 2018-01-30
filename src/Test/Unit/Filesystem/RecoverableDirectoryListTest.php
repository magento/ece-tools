<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->recoverableDirectoryList = new RecoverableDirectoryList(
            $this->environmentMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @param bool $isSymlinkOn
     * @param bool $isStaticInBuild
     * @param array $expected
     * @dataProvider getListDataProvider22
     */
    public function testGetList22(bool $isSymlinkOn, bool $isStaticInBuild, array $expected)
    {
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_STATIC_CONTENT_SYMLINK, $isSymlinkOn],
            ]);
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturnMap([
                ['~2.1', false],
            ]);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn($isStaticInBuild);

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
                'isSymlinkOn' => true,
                'isStaticInBuild' => true,
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
            'no symlink and static in build' => [
                'isSymlinkOn' => false,
                'isStaticInBuild' => true,
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
                        'strategy' => StrategyInterface::STRATEGY_COPY,
                    ],
                ],
            ],
            'symlink and no static in build' => [
                'isSymlinkOn' => true,
                'isStaticInBuild' => false,
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
     * @param bool $isSymlinkOn
     * @param bool $isStaticInBuild
     * @param bool $isGeneratedSymlinkOn
     * @param array $expected
     * @dataProvider getListDataProvider21
     */
    public function testGetList21(bool $isSymlinkOn, bool $isGeneratedSymlinkOn, bool $isStaticInBuild, array $expected)
    {
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_STATIC_CONTENT_SYMLINK, $isSymlinkOn],
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK, $isGeneratedSymlinkOn],
            ]);
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturnMap([
                ['~2.1', true],
            ]);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn($isStaticInBuild);

        $this->assertEquals(
            $expected,
            $this->recoverableDirectoryList->getList()
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getListDataProvider21(): array
    {
        return [
            'static symlink, no generated symlink, static in build' => [
                'isSymlinkOn' => true,
                'isGeneratedSymlinkOn' => false,
                'isStaticInBuild' => true,
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
            'no static symlink, no generated symlink, static in build' => [
                'isSymlinkOn' => false,
                'isGeneratedSymlinkOn' => false,
                'isStaticInBuild' => true,
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
            'static symlink, no generated symlink, no static in build' => [
                'isSymlinkOn' => true,
                'isGeneratedSymlinkOn' => false,
                'isStaticInBuild' => false,
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
                'isSymlinkOn' => true,
                'isGeneratedSymlinkOn' => true,
                'isStaticInBuild' => false,
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
}
