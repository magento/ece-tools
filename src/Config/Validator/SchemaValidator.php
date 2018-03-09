<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Validates configuration types and values by schema.s
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
            self::SCHEMA_VALUE => ['', '-v', '-vv', '-vvv'],
        ],
        StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL => [
            self::SCHEMA_TYPE => ['integer'],
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
                'Item %s has unexpected type %s',
                $key,
                $type
            );
        }

        if ($allowedValues && !in_array($value, $allowedValues)) {
            return sprintf(
                'Item %s has unexpected value %s',
                $key,
                $value
            );
        }
    }
}
