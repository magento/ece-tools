<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy\MergedConfig;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * @inheritdoc
 */
class Deploy implements DeployInterface
{
    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param MergedConfig $mergedConfig
     * @param Schema $schema
     */
    public function __construct(
        MergedConfig $mergedConfig,
        Schema $schema
    ) {
        $this->mergedConfig = $mergedConfig;
        $this->schema = $schema;
    }

    /**
     * Retrieves environment configuration for deploy stage.
     * Tries to do json decode for all string type variables and returns decoded value on success.
     * Returns default value in case of wrong json string for array-type variable.
     * @see Schema for default values.
     *
     * {@inheritdoc}
     */
    public function get(string $name)
    {
        $mergedConfig = $this->mergedConfig->get();

        if (!array_key_exists($name, $mergedConfig)) {
            throw new \RuntimeException(sprintf('Config %s was not defined.', $name));
        }

        $value = $mergedConfig[$name];

        if (!is_string($value)) {
            return $value;
        }

        /**
         * Trying to determine json object in string.
         */
        $decodedValue = json_decode($value, true);

        $value = $decodedValue !== null && json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value;

        $schemaDetails = $this->schema->getSchema()[$name];

        if ($schemaDetails[Schema::SCHEMA_TYPE] === ['array'] && !is_array($value)) {
            $value = $schemaDetails[Schema::SCHEMA_DEFAULT_VALUE][StageConfigInterface::STAGE_DEPLOY];
        }

        return $value;
    }
}
