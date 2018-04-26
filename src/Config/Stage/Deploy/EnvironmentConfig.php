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
     * @return array
     */
    public function getAll(): array
    {
        $variables = $this->convertEnabledDisabledVariables($this->environment->getVariables());

        $intVariables = [
            DeployInterface::VAR_SCD_THREADS,
            DeployInterface::VAR_SCD_COMPRESSION_LEVEL,
        ];

        foreach ($intVariables as $intVar) {
            if (isset($variables[$intVar])) {
                $variables[$intVar] = (int)$variables[$intVar];
            }
        }

        if ($scdThreads = $this->getEnvScdThreads()) {
            $variables[DeployInterface::VAR_SCD_THREADS] = $scdThreads;
        }

        if (isset($variables['STATIC_CONTENT_EXCLUDE_THEMES'])) {
            $variables[DeployInterface::VAR_SCD_EXCLUDE_THEMES] = $variables['STATIC_CONTENT_EXCLUDE_THEMES'];
        }

        return $variables;
    }

    /**
     * Retrieves SCD threads configuration from MAGENTO_CLOUD_VARIABLES or from raw environment data.
     * STATIC_CONTENT_THREADS from MAGENTO_CLOUD_VARIABLES has higher priority then $_ENV['STATIC_CONTENT_THREADS']
     *
     * Raw $_ENV['STATIC_CONTENT_THREADS'] is deprecated.
     *
     * @return int
     */
    private function getEnvScdThreads(): int
    {
        $variables = $this->environment->getVariables();
        $staticDeployThreads = 0;

        if (isset($variables['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$variables['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$_ENV['STATIC_CONTENT_THREADS'];
        }

        return $staticDeployThreads;
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

        if (isset($variables['DO_DEPLOY_STATIC_CONTENT']) &&
            $variables['DO_DEPLOY_STATIC_CONTENT'] === Environment::VAL_DISABLED
        ) {
            $variables[DeployInterface::VAR_SKIP_SCD] = true;
        }

        return $variables;
    }
}
