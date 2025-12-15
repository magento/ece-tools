<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\YamlNormalizer;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ErrorInfoTest extends TestCase
{
    /**
     * @var ErrorInfo
     */
    private $errorInfo;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileListMock       = $this->createMock(FileList::class);
        $this->fileMock           = $this->createMock(File::class);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->errorInfo = new ErrorInfo(
            $this->fileMock,
            $this->fileListMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * Test get error method.
     *
     * @param int $errorCode
     * @param array $expected
     * @return void
     * @throws FileSystemException
     * @dataProvider getErrorDataProvider
     */
    public function testGetError(int $errorCode, array $expected)
    {
        $filePath = __DIR__ . '/_file/schema.error.yaml';

        $this->fileListMock->expects($this->once())
            ->method('getErrorSchema')
            ->willReturn($filePath);

        $fileContents = file_get_contents($filePath);

        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($filePath)
            ->willReturn($fileContents);

        $yamlNormalizerMock = $this->createMock(YamlNormalizer::class);
        $yamlNormalizerMock->expects($this->once())
            ->method('normalize')
            ->with($this->isType('array'))
            ->willReturn(Yaml::parse($fileContents)); // Simulate normalized data

        $errorInfo = new ErrorInfo(
            $this->fileMock,
            $this->fileListMock,
            $yamlNormalizerMock
        );

        $this->assertEquals($expected, $errorInfo->get($errorCode));
    }

    /**
     * Data provider for testGetError method.
     *
     * @return array
     */
    public function getErrorDataProvider(): array
    {
        return [
            [
                12,
                [],
            ],
            [
                2,
                [
                    'title'      => 'Critical error',
                    'suggestion' => 'Critical error suggestion',
                    'stage'      => 'build',
                    'type'       => 'critical',
                ]
            ],
            [
                1001,
                [
                    'title'      => 'Warning error',
                    'suggestion' => 'Warning error suggestion',
                    'stage'      => 'build',
                    'type'       => 'warning',
                ]
            ]
        ];
    }
}
