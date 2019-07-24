<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates build_options.ini configuration.
 *
 * @deprecated As flow with build_options.ini is deprecated
 */
class BuildOptionsIni implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var BuildReader
     */
    private $buildReader;

    /**
     * @var array
     */
    private $buildOptionsMap = [
        'scd_strategy' => StageConfigInterface::VAR_SCD_STRATEGY,
        'exclude_themes' => StageConfigInterface::VAR_SCD_EXCLUDE_THEMES,
        'SCD_COMPRESSION_LEVEL' => StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
        'scd_threads' => StageConfigInterface::VAR_SCD_THREADS,
        'skip_scd' => StageConfigInterface::VAR_SKIP_SCD,
        'VERBOSE_COMMANDS' => StageConfigInterface::VAR_VERBOSE_COMMANDS,
    ];

    /**
     * @param ResultFactory $resultFactory
     * @param SchemaValidator $schemaValidator
     * @param BuildReader $buildReader
     */
    public function __construct(
        ResultFactory $resultFactory,
        SchemaValidator $schemaValidator,
        BuildReader $buildReader
    ) {
        $this->resultFactory = $resultFactory;
        $this->schemaValidator = $schemaValidator;
        $this->buildReader = $buildReader;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $buildOptionsConfig = $this->buildReader->read();
        $errors = [];

        foreach ($buildOptionsConfig as $name => $value) {
            if (!isset($this->buildOptionsMap[$name])) {
                $errors[] = sprintf('Option %s is not allowed', $name);
                continue;
            }

            $value = $this->prepareValue($name, $value);
            $error = $this->schemaValidator->validate(
                $this->buildOptionsMap[$name],
                StageConfigInterface::STAGE_BUILD,
                $value
            );
            if ($error) {
                $error = str_replace($this->buildOptionsMap[$name], $name, $error);
                $errors[] = $error;
            }
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'The build_options.ini file contains an unexpected value',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }

    /**
     * Convert option values in the same way as it made in \Magento\MagentoCloud\Config\Stage\Build::getDeprecatedConfig
     *
     * @param string $name
     * @param mixed $value
     * @return bool|int|string
     */
    private function prepareValue(string $name, $value)
    {
        if (in_array($name, ['SCD_COMPRESSION_LEVEL', 'scd_threads']) && ctype_digit($value)) {
            return (int)$value;
        }

        if ($name === 'skip_scd') {
            return $value === '1';
        }

        if ($name === 'VERBOSE_COMMANDS') {
            return $value === 'enabled' ? '-vv' : '';
        }

        return $value;
    }
}
