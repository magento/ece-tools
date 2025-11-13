<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration schema for .magento.env.yaml file
 */
class Schema
{
    public const SCHEMA_TYPE = 'type';
    public const SCHEMA_ALLOWED_VALUES = 'allowed';
    public const SCHEMA_VALUE_VALIDATORS = 'validators';
    public const SCHEMA_STAGES = 'stages';
    public const SCHEMA_SYSTEM = 'system';
    public const SCHEMA_DEFAULT_VALUE = 'default';
    public const SCHEMA_DESCRIPTION = 'description';
    public const SCHEMA_SKIP_DUMP = 'skip_dump';
    public const SCHEMA_MAGENTO_VERSION = 'magento_version';
    public const SCHEMA_EXAMPLES = 'examples';
    public const SCHEMA_EXAMPLE_COMMENT = 'comment';

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $defaults = [];

    /**
     * @param SystemList $systemList
     * @param Parser $parser
     * @param File $file
     */
    public function __construct(
        SystemList $systemList,
        Parser $parser,
        File $file
    ) {
        $this->systemList = $systemList;
        $this->parser = $parser;
        $this->file = $file;
    }

    /**
     * Returns default values for given stage.
     *
     * @param string $stage
     * @return array
     * @throws FileSystemException
     */
    public function getDefaults(string $stage): array
    {
        if (isset($this->defaults[$stage])) {
            return $this->defaults[$stage];
        }

        foreach ($this->getVariables() as $itemName => $itemOptions) {
            if (isset($itemOptions[self::SCHEMA_DEFAULT_VALUE])
                && is_array($itemOptions[self::SCHEMA_DEFAULT_VALUE])
                && array_key_exists($stage, $itemOptions[self::SCHEMA_DEFAULT_VALUE])
            ) {
                $this->defaults[$stage][$itemName] = $itemOptions[self::SCHEMA_DEFAULT_VALUE][$stage];
            }
        }

        return $this->defaults[$stage] ?? [];
    }

    /**
     * Returns variables configuration.
     *
     * Each configuration item can have next options:
     * 'type' - possible types (string, integer, array, etc..)
     * 'value_validation' - array of possible values or callback validation function
     * 'stage' - possible stages in which item can be configured
     * 'default_values' - array of default values
     *
     * @return array
     * @throws FileSystemException
     */
    public function getVariables(): array
    {
        $schemaFile = $this->systemList->getConfig() . '/schema.yaml';
        $schemaContents = $this->file->fileGetContents($schemaFile);
        if ($schemaContents === false) {
            throw new FileSystemException("Failed to read schema file: {$schemaFile}");
        }
        $schema = $this->parser->parse(
            $schemaContents,
            $this->getYamlParseFlags()
        );

        $schema = $this->normalizeYamlData($schema) ?? [];

        return $schema['variables'] ?? [];
    }

    /**
     * Build YAML parse flags that are supported in current Symfony version.
     *
     * @return int-mask-of<Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS>
     */
    private function getYamlParseFlags(): int
    {
        $flags = 0;
        if (defined(Yaml::class . '::PARSE_CONSTANT')) {
            $flags |= Yaml::PARSE_CONSTANT;
        }
        if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
            $flags |= Yaml::PARSE_CUSTOM_TAGS;
        }
        return $flags;
    }

    /**
     * Recursively normalizes Symfony YAML TaggedValue objects into PHP-native values.
     *
     * Handles the following YAML tags:
     *  - !env: resolves environment variables.
     *  - !include: parses and normalizes included YAML files.
     *  - !php/const: resolves PHP constants (e.g. !php/const:\PDO::ATTR_ERRMODE).
     *  - Other or unknown tags: recursively normalize their values.
     *
     * Ensures all YAML data is converted to scalars or arrays suitable for safe merging.
     *
     * @param mixed $data The parsed YAML data (array, scalar, or TaggedValue).
     * @return mixed The normalized data structure.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") Method is intentionally complex due to tag resolution logic.
     */
    private function normalizeYamlData(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag   = $data->getTag();   // e.g. "php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE"
            $value = $data->getValue();

            // Handle php/const tags (Symfony strips leading '!')
            if (str_starts_with($tag, 'php/const:')) {
                // Extract the constant name
                $constName = substr($tag, strlen('php/const:'));
                $constName = ltrim($constName, '\\');

                // Resolve the constant name to its value if defined
                $constKey = defined($constName) ? constant($constName) : $constName;

                // Handle YAML quirk where ": 1" is parsed literally
                $raw = is_string($value) ? $value : (string)$value;
                $cleanVal = str_replace([':', ' '], '', $raw);
                $constVal = is_numeric($cleanVal) ? (int)$cleanVal : $cleanVal;

                return [$constKey => $constVal];
            }

            // Handle !env
            if ($tag === 'env') {
                $envValue = getenv((string)$value);
                return $envValue !== false ? $envValue : null;
            }

            // Handle !include
            if ($tag === 'include') {
                if (file_exists((string)$value)) {
                    $included = Yaml::parseFile((string)$value);
                    return $this->normalizeYamlData($included);
                }
                return null;
            }

            // Default — recursively normalize nested tagged structures
            $normalized = $this->normalizeYamlData($value);
            return is_array($normalized) ? $normalized : [$normalized];
        }

        // Recursively normalize arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeYamlData($value);
            }
        }

        return $data;
    }
}
