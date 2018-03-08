<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * Validates 'stage' section of environment configuration.
 */
class StageConfigStructure implements ValidatorInterface
{
    const SCHEMA_TYPE = 'type';
    const SCHEMA_VALUE = 'value';

    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param EnvironmentReader $environmentReader
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(EnvironmentReader $environmentReader, Validator\ResultFactory $resultFactory)
    {
        $this->environmentReader = $environmentReader;
        $this->resultFactory = $resultFactory;
    }

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
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];
        $errors = $this->validateChain($config, []);

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'Environment configuration is not valid',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }

    /**
     * Recursive chain validation.
     *
     * @param array $config
     * @param array $errors
     * @return array
     */
    private function validateChain(array $config, array $errors): array
    {
        foreach ($config as $key => $value) {
            if (array_key_exists($key, $this->schema)) {
                if ($error = $this->validateValue($key, $value)) {
                    $errors[] = $error;
                }
            } elseif (is_array($value)) {
                $errors = array_merge(
                    $errors,
                    $this->validateChain($value, $errors)
                );
            }
        }

        return $errors;
    }

    /**
     * Validates the value by schema.
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function validateValue(string $key, $value)
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
