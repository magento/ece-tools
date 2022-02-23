<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates variables configured through MAGENTO_CLOUD_VARIABLES.
 */
class MagentoCloudVariables implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        Validator\ResultFactory $resultFactory
    ) {
        $this->environment = $environment;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates variables configured through MAGENTO_CLOUD_VARIABLES.
     */
    public function validate(): Validator\ResultInterface
    {
        $variables = $this->environment->getVariables();
        $errors = $this->validateIntegers($variables);
        $errors = array_merge($errors, $this->validateEnableDisableValues($variables));

        $possibleVerboseValues = ['-v', '-vv', '-vvv', Environment::VAL_ENABLED];
        if (isset($variables[DeployInterface::VAR_VERBOSE_COMMANDS])
            && !in_array($variables[DeployInterface::VAR_VERBOSE_COMMANDS], $possibleVerboseValues, true)
        ) {
            $errors[] = sprintf(
                '  The variable %s has wrong value "%s" and will be ignored, use one of possible values: %s',
                DeployInterface::VAR_VERBOSE_COMMANDS,
                $variables[DeployInterface::VAR_VERBOSE_COMMANDS],
                implode(', ', array_slice($possibleVerboseValues, 0, -1))
            );
        }

        if ($errors) {
            return $this->resultFactory->error(
                'Environment configuration is not valid',
                implode(PHP_EOL, $errors),
                Error::WARN_ENVIRONMENT_CONFIG_NOT_VALID
            );
        }

        return $this->resultFactory->success();
    }

    /**
     * Validates all integer variables.
     *
     * @param array $variables
     * @return array List of errors
     */
    private function validateIntegers(array $variables): array
    {
        $errors = [];

        $intVariables = [
            DeployInterface::VAR_SCD_THREADS,
        ];

        foreach ($intVariables as $intVarName) {
            if (isset($variables[$intVarName])
                && !is_int($variables[$intVarName])
                && !ctype_digit(strval($variables[$intVarName]))
            ) {
                $errors[] = sprintf(
                    '  The variable %s has wrong value "%s" and will be ignored, use only integer value',
                    $intVarName,
                    $variables[$intVarName]
                );
            }
        }

        if (isset($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL])) {
            if (!ctype_digit(strval($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]))
                || !in_array(intval($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]), range(0, 9))
            ) {
                $errors[] = sprintf(
                    '  The variable %s has wrong value "%s" and will be ignored, use only integer value from 0 to 9',
                    DeployInterface::VAR_SCD_COMPRESSION_LEVEL,
                    $variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]
                );
            } else {
                unset($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]);
            }
        }

        return $errors;
    }

    /**
     * @param array $variables
     * @return array
     */
    private function validateEnableDisableValues(array $variables): array
    {
        $errors = [];
        $enableDisableVariables = [
            DeployInterface::VAR_CLEAN_STATIC_FILES,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
        ];

        $possibleValues = [Environment::VAL_DISABLED, Environment::VAL_ENABLED];
        foreach ($enableDisableVariables as $varName) {
            if (isset($variables[$varName]) && !in_array($variables[$varName], $possibleValues, true)) {
                $errors[] = sprintf(
                    '  The variable %s has wrong value: "%s" and will be ignored, use only %s',
                    $varName,
                    $variables[$varName],
                    implode(' or ', $possibleValues)
                );
            }
        }

        return $errors;
    }
}
