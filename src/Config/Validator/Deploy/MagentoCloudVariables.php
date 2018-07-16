<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

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
                '  Variable %s has wrong value "%s", please use one of possible values: %s',
                DeployInterface::VAR_VERBOSE_COMMANDS,
                $variables[DeployInterface::VAR_VERBOSE_COMMANDS],
                implode(', ', $possibleVerboseValues)
            );
        }

        if ($errors) {
            return $this->resultFactory->create(Validator\Result\Error::ERROR, [
                'error' => 'Environment configuration is not valid',
                'suggestion' => implode(PHP_EOL, $errors),
            ]);
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
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
            DeployInterface::VAR_STATIC_CONTENT_THREADS,
            DeployInterface::VAR_SCD_COMPRESSION_LEVEL,
            DeployInterface::VAR_SCD_THREADS,
        ];

        foreach ($intVariables as $intVarName) {
            if (isset($variables[$intVarName])
                && !is_int($variables[$intVarName])
                && !ctype_digit($variables[$intVarName])
            ) {
                $errors[] = sprintf(
                    '  Variable "%s" has wrong value: "%s". Please use only integer values.',
                    $intVarName,
                    $variables[$intVarName]
                );
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
            DeployInterface::VAR_STATIC_CONTENT_SYMLINK,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
            DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT
        ];

        $possibleValues = [Environment::VAL_DISABLED, Environment::VAL_ENABLED];
        foreach ($enableDisableVariables as $varName) {
            if (isset($variables[$varName]) && !in_array($variables[$varName], $possibleValues, true)) {
                $errors[] = sprintf(
                    '  Variable "%s" has wrong value: "%s". Please use only %s.',
                    $varName,
                    $variables[$varName],
                    implode(' or ', $possibleValues)
                );
            }
        }

        return $errors;
    }
}
