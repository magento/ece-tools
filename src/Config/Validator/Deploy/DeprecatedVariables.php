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
     * Despite PHPMD warnings, this method is ultimately very linear: 1) Check condition; 2) Append error; etc.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate(): Validator\ResultInterface
    {
        $variables = $this->environment->getVariables();

        if (isset($variables[DeployInterface::VAR_VERBOSE_COMMANDS]) &&
            $variables[DeployInterface::VAR_VERBOSE_COMMANDS] === Environment::VAL_ENABLED
        ) {
            return $this->resultFactory->error(
                'The configuration contains deprecated variables or values',
                sprintf(
                    'The %s variable contains deprecated value. Use one of the next values: %s.',
                    DeployInterface::VAR_VERBOSE_COMMANDS,
                    implode(',', ['-v', '-vv', '-vvv'])
                ),
                Error::WARN_DEPRECATED_VARIABLES
            );
        }

        return $this->resultFactory->success();
    }
}
