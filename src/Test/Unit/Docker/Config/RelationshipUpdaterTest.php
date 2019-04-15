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

/**
 * @inheritdoc
 */
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

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
     */
    public function testUpdate()
    {
        $rootDir = '/path/to';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$rootDir . '/docker/config.php'],
                [$rootDir . '/docker/config.php.dist']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('requireFile')
            ->willReturn([
                'MAGENTO_CLOUD_RELATIONSHIPS' => 'encoded_relationships_value',
                'MAGENTO_CLOUD_ROUTES' => 'encoded_routes_value',
            ]);
        $this->cloudVariableEncoderMock->expects($this->exactly(4))
            ->method('decode')
            ->willReturnMap([
                ['encoded_relationships_value', 'decoded_relationships_value'],
                ['encoded_routes_value', 'decoded_routes_value'],
            ]);
        $this->relationshipMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn([
                'database' => ['config'],
                'redis' => ['config'],
            ]);
        $this->phpFormatterMock->expects($this->exactly(4))
            ->method('varExportShort')
            ->willReturnMap([
                [
                    [
                        'database' => ['config'],
                        'redis' => ['config'],
                    ],
                    2,
                    'exported_relationship_value',
                ],
                [
                    'decoded_routes_value',
                    2,
                    'exported_routes_value',
                ],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('filePutContents')
            ->withConsecutive(
                [$rootDir . '/docker/config.php', $this->getConfigForUpdate()],
                [$rootDir . '/docker/config.php.dist', $this->getConfigForUpdate()]
            );

        $this->updater->update();
    }

    /**
     * @return string
     */
    private function getConfigForUpdate(): string
    {
        return <<<TEXT
<?php

return [
    'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(exported_relationship_value)),
    'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(exported_routes_value)),
];

TEXT;
    }
}
