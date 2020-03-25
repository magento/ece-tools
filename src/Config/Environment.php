<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    /**
     * Regex pattern for detecting main branch.
     * The name of the main branch must be started from one of three prefixes:
     *   master - is for integration environment;
     *   production and staging are for production and staging environments respectively.
     */
    private const GIT_MASTER_BRANCH_RE = '/^(master|production|staging)(?:[a-z0-9-]+)?$/';

    public const VAL_ENABLED = 'enabled';
    public const VAL_DISABLED = 'disabled';

    /**
     * The environment variable for controlling the directory nesting level for error reporting
     */
    public const ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL = 'MAGE_ERROR_REPORT_DIR_NESTING_LEVEL';

    /**
     * @var EnvironmentDataInterface
     */
    private $environmentData;

    /**
     * Environment constructor.
     *
     * @param EnvironmentDataInterface $environmentData
     */
    public function __construct(EnvironmentDataInterface $environmentData)
    {
        $this->environmentData = $environmentData;
    }

    /**
     * 'getEnv' method is an abstraction for _ENV and getenv.
     * If _ENV is enabled in php.ini, use that.  If not, fall back to use getenv.
     * returns false if not found
     *
     * @param string $key
     * @return array|string|int|null|bool
     */
    public function getEnv(string $key)
    {
        return $this->environmentData->getEnv($key);
    }

    /**
     * Get a value of the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL.
     *
     * @return array|string|int|null|bool
     */
    public function getEnvVarMageErrorReportDirNestingLevel()
    {
        return $this->getEnv(self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL);
    }

    /**
     * Get routes information.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->environmentData->getRoutes();
    }

    /**
     * Get relationships information.
     *
     * @return array
     */
    public function getRelationships(): array
    {
        return $this->environmentData->getRelationships();
    }

    /**
     * Get relationship information by key.
     *
     * @param string $key
     * @return array
     */
    public function getRelationship(string $key): array
    {
        $relationships = $this->getRelationships();

        return $relationships[$key] ?? [];
    }

    /**
     * Get custom variables.
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->environmentData->getVariables();
    }

    /**
     * @return array
     */
    public function getApplication(): array
    {
        return $this->environmentData->getApplication();
    }

    /**
     * Returns variable value if such variable exists otherwise return $default
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getVariable($name, $default = null)
    {
        return $this->getVariables()[$name] ?? $default;
    }

    /**
     * @return string
     */
    public function getCryptKey(): string
    {
        return $this->getVariable('CRYPT_KEY', '');
    }

    /**
     * Checks that environment uses the main branch depending on environment variable MAGENTO_CLOUD_ENVIRONMENT
     * which contains the name of the git branch.
     *
     * @return bool
     */
    public function isMasterBranch(): bool
    {
        $branchName = $this->environmentData->getBranchName();

        return !empty($branchName)
            && preg_match(self::GIT_MASTER_BRANCH_RE, $branchName);
    }
}
