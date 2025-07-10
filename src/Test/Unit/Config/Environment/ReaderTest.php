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
use PDO;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * @inheritdoc
 */
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
     * @var Reader
     */
    private $reader;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        /**
         * This lines are required for proper running of Magento\MagentoCloud\Test\Unit\Filesystem\Driver\FileTest
         */
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->fileMock = $this->createPartialMock(File::class, ['isExists']);

        $this->reader = new Reader(
            $this->configFileListMock,
            $this->fileMock
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testRead()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $this->reader->read();
        $this->assertEquals(
            [
                'stage' => [
                    'global' => [
                        'SCD_ON_DEMAND' => true,
                        'UPDATE_URLS' => false
                    ],
                    'deploy' => [
                        'DATABASE_CONFIGURATION' => ['host' => '127.0.0.1', 'port' => 3306, 'schema' => 'test_schema'],
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
            ],
            $this->reader->read()
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testReadNotExist()
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
     * @throws FileSystemException
     */
    public function testReadMainConfigWithEmptySectionAndStage()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento-with-empty-sections.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $this->reader->read();
        $this->assertEquals(
            [
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => null,
                    'build' => null,
                ],
                'log' => null,
            ],
            $this->reader->read()
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testReadWithConstants()
    {
        if (!defined(Yaml::class . '::PARSE_CONSTANT') || !defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
            $this->markTestSkipped('Symfony YAML parser does not support PARSE_CONSTANT or PARSE_CUSTOM_TAGS.');
        }

        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento-with-constants.env.yaml');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        // Get the result from Reader
        $result = self::resolveTaggedConstantsDeep($this->reader->read());

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

        $this->assertEquals($expected, $result);
    }

    /**
     * Recursively resolves Symfony YAML TaggedValue constants within an array.
     *
     * This function scans through a nested array structure and replaces any keys or values
     * tagged as `!php/const:` (represented as Symfony\Component\Yaml\Tag\TaggedValue objects)
     * with their corresponding PHP constant values. If a constant is not defined, the original
     * tagged value content is retained.
     *
     * Example:
     * Input: ['driver_options' => [new TaggedValue('php/const:\PDO::MYSQL_ATTR_INIT_COMMAND', 1)]]
     * Output: ['driver_options' => [1002 => 1]]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array $data The parsed YAML data potentially containing TaggedValue instances.
     * @return array The array with all `php/const:` TaggedValue keys and values resolved.
     */
    private static function resolveTaggedConstantsDeep(array $data): array
    {
        $resolved = [];

        foreach ($data as $key => $value) {
            // Resolve tagged keys like !php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE
            if ($key instanceof TaggedValue && strpos($key->getTag(), 'php/const:') === 0) {
                $constName = str_replace('php/const:', '', $key->getTag());
                $constName = ltrim($constName, '\\');
                $resolvedKey = defined($constName) ? constant($constName) : $key->getValue();
            } else {
                $resolvedKey = $key;
            }

            // Recursively resolve nested arrays
            if (is_array($value)) {
                $resolved[$resolvedKey] = self::resolveTaggedConstantsDeep($value);
            } elseif ($value instanceof TaggedValue && strpos($value->getTag(), 'php/const:') === 0) {
                $constName = str_replace('php/const:', '', $value->getTag());
                $constName = ltrim($constName, '\\');

                $constKey = defined($constName) ? constant($constName) : $value->getValue();
                $constVal = $value->getValue() ?? '';

                if (defined($constName) && $constVal) {
                    // Formatted the constant value since YAML parser is not able to parse ': 1' value and
                    // if we make !php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE : 1 to
                    // !php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE: 1 in
                    // src/Test/Unit/Config/Environment/_file/.magento-with-constants.env.yaml file
                    // then PDO::MYSQL_ATTR_LOCAL_INFILE constant is not recognized & not resolved to 1001
                    $cleanVal = str_replace([':', ' '], '', $constVal);
                    $constVal = is_numeric($cleanVal) ? (int) $cleanVal : $cleanVal;

                    $resolved[$resolvedKey][$constKey] = $constVal;
                }
            } else {
                $resolved[$resolvedKey] = $value;
            }
        }

        return $resolved;
    }
}
