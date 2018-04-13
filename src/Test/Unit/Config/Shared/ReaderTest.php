<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Shared;

use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var SharedConfig|MockObject
     */
    private $resolverMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->resolverMock = $this->createMock(SharedConfig::class);

        $this->reader = new Reader(
            $this->fileMock,
            $this->resolverMock
        );
    }

    public function testRead()
    {
        $this->resolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn(__DIR__ . '/_file/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_file/app/etc/config.php')
            ->willReturn(true);

        $this->assertEquals(
            [
                'modules' => [
                    'Some_ModuleName' => 1,
                    'Another_Module' => 0,
                ],
            ],
            $this->reader->read()
        );
    }

    public function testReadFileNotExists()
    {
        $this->resolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn('/path/to/file');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/file')
            ->willReturn(false);

        $this->assertEquals(
            [],
            $this->reader->read()
        );
    }
}
