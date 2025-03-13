<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Validates configuration types and values by schema.
 */
class Validator
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var Validator\ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @param Schema $schema
     * @param ResultFactory $resultFactory
     * @param Validator\ValidatorFactory $validatorFactory
     */
    public function __construct(
        Schema $schema,
        ResultFactory $resultFactory,
        Schema\Validator\ValidatorFactory $validatorFactory
    ) {
        $this->schema = $schema;
        $this->resultFactory = $resultFactory;
        $this->validatorFactory = $validatorFactory;
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
     * @return ResultInterface
     *
     * @throws FileSystemException
     */
    public function validate(string $key, string $stage, $value): ResultInterface
    {
        $schema = $this->schema->getVariables();
        if (!isset($schema[$key])) {
            return $this->resultFactory->error(sprintf(
                'The %s variable is not allowed in configuration.',
                $key
            ));
        }

        $type = gettype($value);
        $allowedType = $schema[$key][Schema::SCHEMA_TYPE] ?? null;
        $allowedValues = $schema[$key][Schema::SCHEMA_ALLOWED_VALUES] ?? [];
        $allowedStages = $schema[$key][Schema::SCHEMA_STAGES] ?? [];
        $validators = $schema[$key][Schema::SCHEMA_VALUE_VALIDATORS] ?? [];

        if ($allowedType && $type !== $allowedType) {
            return $this->resultFactory->error(sprintf(
                'The %s variable contains an invalid value of type %s. Use the following type: %s.',
                $key,
                $type,
                $allowedType
            ));
        }

        if (!in_array($stage, $allowedStages, true)) {
            return $this->resultFactory->error(sprintf(
                'The %s variable is not supposed to be in stage %s. Move it to one of the possible stages: %s.',
                $key,
                $stage,
                implode(', ', $allowedStages)
            ));
        }

        foreach ($validators as $validatorData) {
            $validator = $this->validatorFactory->create(
                $validatorData['class'],
                array_slice(array_values($validatorData), 1)
            );

            $result = $validator->validate($key, $value);

            if ($result instanceof Error) {
                return $result;
            }
        }

        if ($allowedValues && !in_array($value, $allowedValues, false)) {
            return $this->resultFactory->error(sprintf(
                'The %s variable contains an invalid value %s. Use one of the available value options: %s.',
                $key,
                $value,
                implode(', ', array_filter($allowedValues))
            ));
        }

        return $this->resultFactory->success();
    }
}
