<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Deploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class WriterTest extends TestCase
{
    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var Reader|Mock
     */
    private $readerMock;

    /**
     * @var Writer
     */
    private $writer;

    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->writer = new Writer(
            $this->readerMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    /**
     * @param array $config
     * @param string $updatedConfig
     * @dataProvider writeDataProvider
     */
    public function testWrite(array $config, $updatedConfig)
    {
        $filePath = '/path/to/file';
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($filePath);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($filePath, $updatedConfig);

        $this->writer->write($config);
    }

    /**
     * @return array
     */
    public function writeDataProvider()
    {
        return [
            [
                [],
                "<?php\nreturn array (\n);"
            ],
            [
                ['key' => 'value'],
                "<?php\nreturn array (\n  'key' => 'value',\n);"
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key2' => 'value2',\n);"
            ]
        ];
    }

    /**
     * @param array $config
     * @param array $currentConfig
     * @param string $updatedConfig
     * @dataProvider readDataProvider
     */
    public function testUpdate(array $config, array $currentConfig, $updatedConfig)
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
     * @return array
     */
    public function readDataProvider()
    {
        return [
            [
                [],
                [],
                "<?php\nreturn array (\n);"
            ],
            [
                ['key' => 'value'],
                ['key1' => 'value1'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key' => 'value',\n);"
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2'],
                ['key1' => 'value0', 'key3' => 'value3'],
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key3' => 'value3',\n  'key2' => 'value2',\n);"
            ]
        ];
    }
}
