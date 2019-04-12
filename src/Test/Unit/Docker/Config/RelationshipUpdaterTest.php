<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Config;

use Magento\MagentoCloud\Docker\Config\Relationship;
use Magento\MagentoCloud\Docker\Config\RelationshipUpdater;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\CloudVariableEncoder;
use Magento\MagentoCloud\Util\PhpFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelationshipUpdaterTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var CloudVariableEncoder|MockObject
     */
    private $cloudVariableEncoderMock;

    /**
     * @var Relationship|MockObject
     */
    private $relationshipMock;

    /**
     * @var PhpFormatter|MockObject
     */
    private $phpFormatterMock;

    /**
     * @var RelationshipUpdater
     */
    private $updater;

    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->cloudVariableEncoderMock = $this->createMock(CloudVariableEncoder::class);
        $this->relationshipMock = $this->createMock(Relationship::class);
        $this->phpFormatterMock = $this->createMock(PhpFormatter::class);

        $this->updater = new RelationshipUpdater(
            $this->directoryListMock,
            $this->fileMock,
            $this->cloudVariableEncoderMock,
            $this->relationshipMock,
            $this->phpFormatterMock
        );
    }

    /**
     * @param array $configFileData
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $configFileData)
    {
        $rootDir = '/path/to';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('requireFile')
            ->willReturn($configFileData);
        $this->cloudVariableEncoderMock->expects($this->exactly(count($configFileData) * 2))
            ->method('decode');

        $this->updater->update();
    }

    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        return [
            [
                []
            ]
        ];
    }
}
