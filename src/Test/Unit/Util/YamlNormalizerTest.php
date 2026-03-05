<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\YamlNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Tag\TaggedValue;

class YamlNormalizerTest extends TestCase
{
    /**
     * @var YamlNormalizer
     */
    private YamlNormalizer $normalizer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->normalizer = new YamlNormalizer();
    }

    /**
     * Test normalizing php/const tag
     *
     * @return void
     */
    public function testNormalizePhpConstTag(): void
    {
        $data = new TaggedValue('php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE', ' 1');
        $result = $this->normalizer->normalize($data);

        $expectedKey = @constant('PDO::MYSQL_ATTR_LOCAL_INFILE');
        $this->assertArrayHasKey($expectedKey, $result);
        $this->assertSame(1, $result[$expectedKey]);
    }

    /**
     * Test normalizing php/const tag with undefined constant
     *
     * @return void
     */
    public function testNormalizePhpConstTagUndefinedConstant(): void
    {
        $data = new TaggedValue('php/const:\Nonexistent::CONSTANT', '42');
        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('Nonexistent::CONSTANT', $result);
        $this->assertSame(42, $result['Nonexistent::CONSTANT']);
    }

    /**
     * Test normalizing env tag with existing environment variable
     *
     * @return void
     */
    public function testNormalizeEnvTagWithExistingValue(): void
    {
        putenv('TEST_ENV_VAR=cloud');
        $data = new TaggedValue('env', 'TEST_ENV_VAR');

        $result = $this->normalizer->normalize($data);
        $this->assertSame('cloud', $result);
    }

    /**
     * Test normalizing env tag with missing environment variable
     *
     * @return void
     */
    public function testNormalizeEnvTagWithMissingValue(): void
    {
        putenv('TEST_MISSING_ENV_VAR'); // unset
        $data = new TaggedValue('env', 'TEST_MISSING_ENV_VAR');

        $result = $this->normalizer->normalize($data);
        $this->assertNull($result);
    }

    /**
     * Test normalizing include tag with existing file
     *
     * @return void
     */
    public function testNormalizeIncludeTagWithExistingFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'yaml_');
        file_put_contents($tempFile, "key: value\n");

        $data = new TaggedValue('include', $tempFile);
        $result = $this->normalizer->normalize($data);

        $this->assertSame(['key' => 'value'], $result);

        unlink($tempFile);
    }

    /**
     * Test normalizing include tag with missing file
     *
     * @return void
     */
    public function testNormalizeIncludeTagWithMissingFile(): void
    {
        $data = new TaggedValue('include', '/nonexistent/file.yaml');
        $result = $this->normalizer->normalize($data);

        $this->assertNull($result);
    }

    /**
     * Test normalizing nested tagged values
     *
     * @return void
     */
    public function testNormalizeNestedTaggedValue(): void
    {
        $inner = new TaggedValue('php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE', ' 1');
        $outer = new TaggedValue('custom', ['nested' => $inner]);

        $result = $this->normalizer->normalize($outer);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Check if the result has the expected structure
        $firstElement = $result[0] ?? $result;
        $this->assertIsArray($firstElement);
        $this->assertArrayHasKey('nested', $firstElement);
        $expectedKey = @constant('PDO::MYSQL_ATTR_LOCAL_INFILE');
        $this->assertArrayHasKey($expectedKey, $firstElement['nested']);
    }

    /**
     * Test normalizing array with mixed tagged and untagged values
     *
     * @return void
     */
    public function testNormalizeArrayRecursively(): void
    {
        $data = [
            'a' => new TaggedValue('php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE', ' 1'),
            'b' => 'plain',
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('a', $result);
        $expectedKey = @constant('PDO::MYSQL_ATTR_LOCAL_INFILE');
        $this->assertArrayHasKey($expectedKey, $result['a']);
        $this->assertSame('plain', $result['b']);
    }

    /**
     * Test normalizing scalar values
     *
     * @return void
     */
    public function testNormalizeScalarValue(): void
    {
        $this->assertSame('hello', $this->normalizer->normalize('hello'));
        $this->assertSame(123, $this->normalizer->normalize(123));
        $this->assertSame(45.67, $this->normalizer->normalize(45.67));
        $this->assertTrue($this->normalizer->normalize(true));
        $this->assertFalse($this->normalizer->normalize(false));
        $this->assertNull($this->normalizer->normalize(null));
    }

    /**
     * Test normalizing php/const with complex value formats
     *
     * @return void
     */
    public function testNormalizePhpConstTagWithComplexValues(): void
    {
        // Test with colon and spaces (YAML quirk handling)
        $data = new TaggedValue('php/const:\PDO::ATTR_ERRMODE', ': 2');
        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey(\PDO::ATTR_ERRMODE, $result);
        $this->assertSame(2, $result[\PDO::ATTR_ERRMODE]);
    }

    /**
     * Test normalizing php/const with non-string values
     *
     * @return void
     */
    public function testNormalizePhpConstTagWithNonStringValue(): void
    {
        $data = new TaggedValue('php/const:\PDO::ATTR_TIMEOUT', 30);
        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey(\PDO::ATTR_TIMEOUT, $result);
        $this->assertSame(30, $result[\PDO::ATTR_TIMEOUT]);
    }

    /**
     * Test normalizing php/const with leading backslash
     *
     * @return void
     */
    public function testNormalizePhpConstTagWithLeadingBackslash(): void
    {
        $data = new TaggedValue('php/const:\\PDO::ATTR_CASE', '1');
        $result = $this->normalizer->normalize($data);

        $this->assertArrayHasKey(\PDO::ATTR_CASE, $result);
        $this->assertSame(1, $result[\PDO::ATTR_CASE]);
    }

    /**
     * Test normalizing unknown tag with scalar value
     *
     * @return void
     */
    public function testNormalizeUnknownTagWithScalar(): void
    {
        $data = new TaggedValue('unknown_tag', 'some_value');
        $result = $this->normalizer->normalize($data);

        $this->assertSame(['some_value'], $result);
    }

    /**
     * Test normalizing unknown tag with array value
     *
     * @return void
     */
    public function testNormalizeUnknownTagWithArray(): void
    {
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        $data = new TaggedValue('unknown_tag', $arrayValue);
        $result = $this->normalizer->normalize($data);

        $this->assertSame($arrayValue, $result);
    }

    /**
     * Test normalizing empty array
     *
     * @return void
     */
    public function testNormalizeEmptyArray(): void
    {
        $result = $this->normalizer->normalize([]);
        $this->assertSame([], $result);
    }

    /**
     * Test normalizing complex nested structure
     *
     * @return void
     */
    public function testNormalizeComplexNestedStructure(): void
    {
        putenv('COMPLEX_TEST_VAR=complex_value');
        
        $data = [
            'simple' => 'value',
            'const' => new TaggedValue('php/const:\PDO::ATTR_ERRMODE', '1'),
            'env' => new TaggedValue('env', 'COMPLEX_TEST_VAR'),
            'nested' => [
                'inner_const' => new TaggedValue('php/const:\PDO::ATTR_TIMEOUT', '30'),
                'normal' => 'normal_value',
                'deep' => [
                    'deeper_const' => new TaggedValue('php/const:\PDO::ATTR_CASE', '2')
                ]
            ]
        ];

        $result = $this->normalizer->normalize($data);

        $this->assertSame('value', $result['simple']);
        $this->assertArrayHasKey(\PDO::ATTR_ERRMODE, $result['const']);
        $this->assertSame(1, $result['const'][\PDO::ATTR_ERRMODE]);
        $this->assertSame('complex_value', $result['env']);
        $this->assertArrayHasKey(\PDO::ATTR_TIMEOUT, $result['nested']['inner_const']);
        $this->assertSame(30, $result['nested']['inner_const'][\PDO::ATTR_TIMEOUT]);
        $this->assertSame('normal_value', $result['nested']['normal']);
        $this->assertArrayHasKey(\PDO::ATTR_CASE, $result['nested']['deep']['deeper_const']);
        $this->assertSame(2, $result['nested']['deep']['deeper_const'][\PDO::ATTR_CASE]);

        // Clean up
        putenv('COMPLEX_TEST_VAR');
    }
}
