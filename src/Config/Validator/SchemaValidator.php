<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Validates configuration types and values by schema.
 */
class SchemaValidator
{
    const SCHEMA_TYPE = 'type';
    const SCHEMA_VALUE = 'value';

    /**
     * @var array
     */
    private $schema = [
        StageConfigInterface::VAR_VERBOSE_COMMANDS => [
            self::SCHEMA_TYPE => ['string'],
            self::SCHEMA_VALUE => ['', '-v', '-vv', '-vvv', Environment::VAL_ENABLED],
        ],
        StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL => [
            self::SCHEMA_TYPE => ['integer', 'string'],
        ],
    ];

    /**
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    public function validate(string $key, $value)
    {
        $type = gettype($value);
        $allowedTypes = $this->schema[$key][self::SCHEMA_TYPE] ?? [];
        $allowedValues = $this->schema[$key][self::SCHEMA_VALUE] ?? [];

        if ($allowedTypes && !in_array($type, $allowedTypes)) {
            return sprintf(
                'Item %s has unexpected type %s. Please use one of next types: %s',
                $key,
                $type,
                implode(', ', $allowedTypes)
            );
        }

        if ($allowedValues && !in_array($value, $allowedValues)) {
            return sprintf(
                'Item %s has unexpected value %s. Please use one of next values: %s',
                $key,
                $value,
                implode(', ', array_filter($allowedValues))
            );
        }
    }
}
