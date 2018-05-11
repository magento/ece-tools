<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
     * STATIC_CONTENT_THREADS from MAGENTO_CLOUD_VARIABLES has higher priority then $_ENV['STATIC_CONTENT_THREADS']
     * Raw $_ENV['STATIC_CONTENT_THREADS'] is deprecated.
     *
     * @return array
     */
    public function getAll(): array
    {
        $variables = $this->convertEnabledDisabledVariables($this->environment->getVariables());

        if (isset($variables['STATIC_CONTENT_THREADS'])) {
            $envScdThreads = $variables['STATIC_CONTENT_THREADS'];
            unset($variables['STATIC_CONTENT_THREADS']);
        } else {
            $envScdThreads = $this->environment->getEnv('STATIC_CONTENT_THREADS');
        }

        if (ctype_digit($envScdThreads)) {
            $variables[DeployInterface::VAR_SCD_THREADS] = (int)$envScdThreads;
        }

        if (isset($variables['STATIC_CONTENT_EXCLUDE_THEMES'])) {
            $variables[DeployInterface::VAR_SCD_EXCLUDE_THEMES] = $variables['STATIC_CONTENT_EXCLUDE_THEMES'];
            unset($variables['STATIC_CONTENT_EXCLUDE_THEMES']);
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
            DeployInterface::VAR_STATIC_CONTENT_SYMLINK,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
        ];

        foreach ($disabledFlow as $disabledVar) {
            if (isset($variables[$disabledVar]) && $variables[$disabledVar] === Environment::VAL_DISABLED) {
                $variables[$disabledVar] = false;
            }
        }

        if (isset($variables['DO_DEPLOY_STATIC_CONTENT'])) {
            $variables[DeployInterface::VAR_SKIP_SCD] =
                $variables['DO_DEPLOY_STATIC_CONTENT'] === Environment::VAL_DISABLED;
            unset($variables['DO_DEPLOY_STATIC_CONTENT']);
        }

        return $variables;
    }
}
