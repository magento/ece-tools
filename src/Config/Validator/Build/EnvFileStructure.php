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
 * Class EnvFileStructure.
 */
class EnvFileStructure implements ValidatorInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @param EnvironmentReader $environmentReader
     */
    public function __construct(EnvironmentReader $environmentReader)
    {
        $this->environmentReader = $environmentReader;
    }

    /**
     * @var array
     */
    private $schema = [
        StageConfigInterface::VAR_VERBOSE_COMMANDS => [
            'type' => ['string'],
            'values' => ['-v', '-vv', '-vvv'],
        ],
    ];

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->environmentReader->read();

        $errors = $this->validateChain($config, []);

        die(var_dump($errors));
    }

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

    private function validateValue(string $key, $value)
    {
        $type = gettype($value);
        $allowedTypes = $this->schema[$key]['type'];
        $allowedValues = $this->schema[$key]['values'];

        if ($allowedTypes && !in_array($type, $allowedTypes)) {
            return sprintf(
                'Item %s has wrong type %s',
                $key,
                $type
            );
        }

        if ($allowedValues && !in_array($value, $allowedValues)) {
            return sprintf(
                'Item %s has wrong value %s',
                $key,
                $value
            );
        }
    }
}
