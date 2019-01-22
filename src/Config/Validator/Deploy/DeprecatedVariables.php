<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates on using deprecated variables or values.
 */
class DeprecatedVariables implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * The source of global and cloud variables.
     *
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        ResultFactory $resultFactory
    ) {
        $this->environment = $environment;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates configuration on using deprecated variables or values.
     *
     * {@inheritdoc}
     */
    public function validate(): Validator\ResultInterface
    {
        $variables = $this->environment->getVariables();
        $errors = [];

        if (isset($variables[DeployInterface::VAR_VERBOSE_COMMANDS]) &&
            $variables[DeployInterface::VAR_VERBOSE_COMMANDS] === Environment::VAL_ENABLED
        ) {
            $errors[] = sprintf(
                'The %s variable contains deprecated value. Use one of the next values: %s.',
                DeployInterface::VAR_VERBOSE_COMMANDS,
                implode(',', ['-v', '-vv', '-vvv'])
            );
        }

        if (isset($variables[DeployInterface::VAR_SCD_EXCLUDE_THEMES])) {
            $errors[] = sprintf(
                'The %s variable is deprecated. Use %s instead.',
                DeployInterface::VAR_SCD_EXCLUDE_THEMES,
                DeployInterface::VAR_SCD_MATRIX
            );
        }

        if ($this->environment->getEnv(DeployInterface::VAR_STATIC_CONTENT_THREADS)
            || isset($variables[DeployInterface::VAR_STATIC_CONTENT_THREADS])
        ) {
            $errors[] = sprintf(
                'The %s variable is deprecated. Use %s instead.',
                DeployInterface::VAR_STATIC_CONTENT_THREADS,
                DeployInterface::VAR_SCD_THREADS
            );
        }

        if ($this->environment->getEnv(DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT)
            || isset($variables[DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT])
        ) {
            $errors[] = sprintf(
                'The %s variable is deprecated. Use %s instead.',
                DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT,
                DeployInterface::VAR_SKIP_SCD
            );
        }

        if ($errors) {
            return $this->resultFactory->error(
                'The configuration contains deprecated variables or values',
                implode(PHP_EOL, $errors)
            );
        }

        return $this->resultFactory->success();
    }
}
