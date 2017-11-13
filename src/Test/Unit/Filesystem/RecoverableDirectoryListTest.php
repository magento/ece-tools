<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Config\Environment;
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

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->recoverableDirectoryList = new RecoverableDirectoryList(
            $this->environmentMock
        );
    }

    /**
     * @param bool $isSymlinkOn
     * @param array $expected
     * @dataProvider getListDataProvider
     */
    public function testGetList(bool $isSymlinkOn, array $expected)
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticContentSymlinkOn')
            ->willReturn($isSymlinkOn);

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
                [
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'symlink'
                    ],
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy'
                    ]
                ]
            ],
            [
                false,
                [
                    [
                        'directory' => 'var/view_preprocessed',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'app/etc',
                        'strategy' => 'copy'
                    ],
                    [
                        'directory' => 'pub/media',
                        'strategy' => 'copy'
                    ]
                ]
            ]
        ];
    }
}
