<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var Manager|Mock
     */
    private $flagManagerMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->flagManagerMock = $this->createMock(Manager::class);

        $this->recoverableDirectoryList = new RecoverableDirectoryList(
            $this->environmentMock,
            $this->flagManagerMock
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
        $this->environmentMock->expects($this->once())
            ->method('isStaticContentSymlinkOn')
            ->willReturn($isSymlinkOn);

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployInBuild::KEY)
            ->willReturn($isStaticInBuild);
        $this->assertEquals(
            $expected,
            $this->recoverableDirectoryList->getList()
        );
    }

    public function getListDataProvider()
    {
        return [
            [
                true,
                true,
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'sub_symlink'
                    ],
                ]
            ],
            [
                false,
                true,
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/static',
                        'strategy' => 'copy'
                    ],
                ]
            ],
            [
                true,
                false,
                [
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy'
                    ],
                ]
            ]
        ];
    }
}
