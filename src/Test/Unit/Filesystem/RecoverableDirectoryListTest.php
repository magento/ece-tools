<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
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
     * @dataProvider getListDataProvider21
     */
    public function testGetList(bool $isSymlinkOn, bool $isStaticInBuild, bool $is22, bool $is21, array $expected)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_STATIC_CONTENT_SYMLINK)
            ->willReturn($isSymlinkOn);
        $this->magentoVersionMock->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->withConsecutive(['2.1'], ['2.2'])
            ->willReturnOnConsecutiveCalls($is21, $is22);
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
            [
                true, // $isSymlinkOn
                true, // $isStaticInBuild
                true, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'sub_symlink',
                    ],
                ],
            ],
            [
                false, // $isSymlinkOn
                true, // $isStaticInBuild
                true, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'copy',
                    ],
                ],
            ],
            [
                true, // $isSymlinkOn
                false, // $isStaticInBuild
                true, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                ],
            ],
        ];
    }

    public function getListDataProvider21(): array
    {
        return [
            [
                true, // $isSymlinkOn
                true, // $isStaticInBuild
                false, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'sub_symlink',
                    ],
                ],
            ],
            [
                false, // $isSymlinkOn
                true, // $isStaticInBuild
                false, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'copy',
                    ],
                ],
            ],
            [
                true, // $isSymlinkOn
                false, // $isStaticInBuild
                false, // $is22
                true, // $is21
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/di',
                        'strategy' => 'copy',
                    ],
                    [
                        'directory' => 'var/generation',
                        'strategy' => 'copy',
                    ],
                ],
            ],
        ];
    }
}
