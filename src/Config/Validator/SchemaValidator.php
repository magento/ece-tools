<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Schema;

/**
 * Validates configuration types and values by schema.
 */
class SchemaValidator
{
    const SCHEMA_TYPE = 'type';
    const SCHEMA_VALUE = 'value';

    const SCHEMA_STAGE = 'stage';

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Validates configuration item:
     * - item exists in configuration schema
     * - item configured in correct stage
     * - item has correct type (integer, string, etc)
     * - item value is correct
     *
     * @param string $key
     * @param string $stage
     * @param mixed $value
     * @return null|string
     */
    public function validate(string $key, string $stage, $value)
    {
        $schema = $this->schema->getSchema();
        if (!isset($schema[$key])) {
            return sprintf('The %s variable is not allowed in configuration.', $key);
        }

        $type = gettype($value);
        $allowedTypes = $schema[$key][Schema::SCHEMA_TYPE] ?? [];
        $allowedValues = $schema[$key][Schema::SCHEMA_VALUE_VALIDATION] ?? [];
        $allowedStages = $schema[$key][Schema::SCHEMA_STAGE] ?? [];

        if ($allowedTypes && !in_array($type, $allowedTypes)) {
            return sprintf(
                'The %s variable contains an invalid value of type %s. Use the following types: %s.',
                $key,
                $type,
                implode(', ', $allowedTypes)
            );
        }

        if (!in_array($stage, $allowedStages)) {
            return sprintf(
                'The %s variable is not supposed to be in stage %s. Move it to one of the possible stages: %s.',
                $key,
                $stage,
                implode(', ', $allowedStages)
            );
        }

        if (is_callable($allowedValues)) {
            return $allowedValues($key, $value);
        }

        if ($allowedValues && !in_array($value, $allowedValues)) {
            return sprintf(
                'The %s variable contains an invalid value %s. Use one of the available value options: %s.',
                $key,
                $value,
                implode(', ', array_filter($allowedValues))
            );
        }
    }
}
