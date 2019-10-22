<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Resolves environment variables and maps them to appropriate format.
 */
class EnvironmentConfig
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Resolves environment values with and adds custom mappings.
     *
     * @return array
     */
    public function getAll(): array
    {
        $variables = $this->convertEnabledDisabledVariables($this->environment->getVariables());
        $variables = $this->convertIntegerVariables($variables);

        if (isset($variables[DeployInterface::VAR_VERBOSE_COMMANDS]) &&
            !in_array($variables[DeployInterface::VAR_VERBOSE_COMMANDS], ['-v', '-vv', '-vvv'])
        ) {
            unset($variables[DeployInterface::VAR_VERBOSE_COMMANDS]);
        }

        return $variables;
    }

    /**
     * Converts all existence variables with disabled/enabled values to appropriate format.
     *
     * @param array $variables
     * @return array
     */
    private function convertEnabledDisabledVariables(array $variables): array
    {
        if (isset($variables[DeployInterface::VAR_VERBOSE_COMMANDS])
            && $variables[DeployInterface::VAR_VERBOSE_COMMANDS] === Environment::VAL_ENABLED
        ) {
            $variables[DeployInterface::VAR_VERBOSE_COMMANDS] = '-vvv';
        }

        $disabledFlow = [
            DeployInterface::VAR_CLEAN_STATIC_FILES,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
        ];

        foreach ($disabledFlow as $disabledVar) {
            if (isset($variables[$disabledVar]) && $variables[$disabledVar] === Environment::VAL_DISABLED) {
                $variables[$disabledVar] = false;
            }
        }

        return $variables;
    }

    /**
     * Converts all integer variables.
     * Unset from integer-type variables that have non-integer value.
     * Unset SCD_COMPRESSION_LEVEL variable if it not in range 0-9
     *
     * @param array $variables
     * @return array
     */
    private function convertIntegerVariables(array $variables): array
    {
        foreach ([DeployInterface::VAR_SCD_THREADS, DeployInterface::VAR_SCD_COMPRESSION_LEVEL] as $varName) {
            if (isset($variables[$varName])) {
                if (!is_int($variables[$varName]) && !ctype_digit($variables[$varName])) {
                    unset($variables[$varName]);
                } else {
                    $variables[$varName] = (int)$variables[$varName];
                }
            }
        }

        if (isset($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL])
            && !in_array(intval($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]), range(0, 9))
        ) {
            unset($variables[DeployInterface::VAR_SCD_COMPRESSION_LEVEL]);
        }

        return $variables;
    }
}
