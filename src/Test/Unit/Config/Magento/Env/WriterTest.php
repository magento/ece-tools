<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Magento\Env;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\Writer;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class WriterTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->readerMock = $this->createMock(ReaderInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->writer = new Writer(
            $this->readerMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    /**
     * Test create method.
     *
     * @param array $config
     * @param string $updatedConfig
     * @dataProvider createDataProvider
     * @return void
     * @throws FileSystemException
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(array $config, $updatedConfig): void
    {
        $filePath = '/path/to/file';
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($filePath);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($filePath, $updatedConfig);

        $this->writer->create($config);
    }

    /**
     * Data provider for testCreate method.
     * @return array
     */
    public static function createDataProvider(): array
    {
        return [
            [
                [],
                "<?php\nreturn array (\n);",
            ],
            [
                ['key' => 'value'],
                "<?php\nreturn array (\n  'key' => 'value',\n);",
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key2' => 'value2',\n);",
            ],
        ];
    }

    /**
     * Test update method.
     *
     * @param array $config
     * @param array $currentConfig
     * @param string $updatedConfig
     * @dataProvider getUpdateDataProvider
     * @throws FileSystemException
     */
    #[DataProvider('getUpdateDataProvider')]
    public function testUpdate(array $config, array $currentConfig, $updatedConfig): void
    {
        $filePath = '/path/to/file';
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($filePath);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($currentConfig);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($filePath, $updatedConfig);

        $this->writer->update($config);
    }

    /**
     * Data provider for testUpdate method.
     *
     * @return array
     */
    public static function getUpdateDataProvider(): array
    {
        return [
            [
                [],
                [],
                "<?php\nreturn array (\n);",
            ],
            [
                ['key' => 'value'],
                ['key1' => 'value1'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key' => 'value',\n);",
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2'],
                ['key1' => 'value0', 'key3' => 'value3'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key3' => 'value3',\n  'key2' => 'value2',\n);",
            ],
            [
                [
                    'key1' => [
                        'key12' => 'value2new',
                        'key13' => 'value3new',
                    ]
                ],
                [
                    'key1' => [
                        'key11' => 'value1',
                        'key12' => 'value2',
                    ]
                ],
                "<?php\nreturn array (\n  'key1' => \n  array (\n    'key11' => 'value1',\n" .
                    "    'key12' => 'value2new',\n    'key13' => 'value3new',\n  ),\n);"
            ],
            [
                [
                    'system' => [
                        'default' => [
                            'catalog' => [
                                'search' => [
                                    'engine' => 'elasticsearch'
                                ],
                            ],
                        ],
                    ],
                    'key1' => [
                        'key12' => 'value2new',
                        'key13' => 'value3new',
                    ]
                ],
                [
                    'system' => [
                        'default' => [
                            'category' => [
                                'option' => 'value'
                            ],
                            'catalog' => [
                                'search' => [
                                    'engine' => 'mysql',
                                    'host' => 'localhost',
                                ],
                            ],
                        ],
                    ],
                    'key1' => [
                        'key11' => 'value1',
                        'key12' => 'value2',
                    ]
                ],
                "<?php\nreturn array (\n  'system' => \n  array (\n    'default' => \n    array (\n" .
                    "      'category' => \n      array (\n        'option' => 'value',\n      ),\n" .
                    "      'catalog' => \n      array (\n        'search' => \n        array (\n" .
                    "          'engine' => 'elasticsearch',\n          'host' => 'localhost',\n        ),\n      ),\n" .
                    "    ),\n  ),\n  'key1' => \n  array (\n    'key11' => 'value1',\n" .
                    "    'key12' => 'value2new',\n    'key13' => 'value3new',\n  ),\n);"
            ]
        ];
    }
}
