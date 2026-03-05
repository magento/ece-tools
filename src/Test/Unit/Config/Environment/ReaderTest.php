<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Environment;

use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Test\Unit\Filesystem\Driver\FileTest;
use Magento\MagentoCloud\Util\YamlNormalizer;
use PDO;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ReaderTest extends TestCase
{
    use PHPMock;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        /**
         * This lines are required for proper running of FileTest
         */
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->fileMock           = $this->createPartialMock(File::class, ['isExists']);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->reader = new Reader(
            $this->configFileListMock,
            $this->fileMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * Test read method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testRead(): void
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $expected = [
            'stage' => [
                'global' => [
                    'SCD_ON_DEMAND' => true,
                    'UPDATE_URLS' => false
                ],
                'deploy' => [
                    'DATABASE_CONFIGURATION' => [
                        'host' => '127.0.0.1',
                        'port' => 3306,
                        'schema' => 'test_schema'
                    ],
                    'SCD_THREADS' => 5
                ],
            ],
            'log' => [
                'gelf' => [
                    'min_level' => 'info',
                    'use_default_formatter' => true,
                    'additional' => ['project' => 'project', 'app_id' => 'app'],
                ],
            ],
        ];

        $this->yamlNormalizerMock
            ->method('normalize')
            ->willReturnCallback(fn($data) => $expected);

        $this->assertEquals($expected, $this->reader->read());
    }

    /**
     * Test read method when file does not exist.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testReadNotExist(): void
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(false);

        $this->assertEquals([], $this->reader->read());
    }

    /**
     * Test read method with main config with empty section and stage.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testReadMainConfigWithEmptySectionAndStage(): void
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento-with-empty-sections.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $expected = [
            'stage' => [
                'global' => ['SCD_ON_DEMAND' => true],
                'deploy' => null,
                'build' => null,
            ],
            'log' => null,
        ];

        $this->yamlNormalizerMock
            ->method('normalize')
            ->willReturnCallback(fn($data) => $expected);

        $this->assertEquals($expected, $this->reader->read());
    }

    /**
     * Test read method with constants.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testReadWithConstants(): void
    {
        if (!defined(Yaml::class . '::PARSE_CONSTANT')
           || !defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
            $this->markTestSkipped('Symfony YAML parser does not support PARSE_CONSTANT or PARSE_CUSTOM_TAGS.');
        }

        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento-with-constants.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $expected = [
            'stage' => [
                'deploy' => [
                    'DATABASE_CONFIGURATION' => [
                        'connection' => [
                            'default' => ['driver_options' => [1001 => 1]],
                            'indexer' => ['driver_options' => [1002 => 1]],
                        ],
                        '_merge' => true,
                    ],
                ],
            ],
        ];

        // Mock normalization to simulate constant resolution
        $this->yamlNormalizerMock
            ->method('normalize')
            ->willReturnCallback(fn($data) => $expected);

        $this->assertEquals($expected, $this->reader->read());
    }
}
