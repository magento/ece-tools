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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->recoverableDirectoryList = new RecoverableDirectoryList(
            $this->environmentMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @param bool $isSymlinkOn
     * @param bool $isStaticInBuild
     * @param array $expected
     * @dataProvider getListDataProvider
     */
    public function testGetList(bool $isSymlinkOn, bool $isStaticInBuild, array $expected)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_STATIC_CONTENT_SYMLINK)
            ->willReturn($isSymlinkOn);
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
    public function getListDataProvider(): array
    {
        return [
            [
                true,
                true,
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
                false,
                true,
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
                true,
                false,
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
}
