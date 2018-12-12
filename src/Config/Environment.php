<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\System\Variables;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    /**
     * @var Variables
     */
    private $systemConfig;

    /**
     * Regex pattern for detecting main branch.
     * The name of the main branch must be started from one of three prefixes:
     *   master - is for integration environment;
     *   production and staging are for production and staging environments respectively.
     */
    const GIT_MASTER_BRANCH_RE = '/^(master|production|staging)(?:-[a-z0-9]+)?$/i';

    /**
     * @deprecated Threads environment variables must be used.
     */
    const CLOUD_MODE_ENTERPRISE = 'enterprise';

    const VAL_ENABLED = 'enabled';
    const VAL_DISABLED = 'disabled';

    const DEFAULT_ADMIN_URL = 'admin';
    const DEFAULT_ADMIN_NAME = 'admin';
    const DEFAULT_ADMIN_FIRSTNAME = 'Admin';
    const DEFAULT_ADMIN_LASTNAME = 'Username';

    /**
     * Environment constructor.
     * @param Variables $systemConfig
     */
    public function __construct(
        Variables $systemConfig
    ) {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @var array
     */
    private $data = [];

    /**
     * 'getEnv' method is an abstraction for _ENV and getenv.
     * If _ENV is enabled in php.ini, use that.  If not, fall back to use getenv.
     * returns false if not found
     *
     * @param string $key
     * @return array|string|int|null
     */
    public function getEnv(string $key)
    {
        return $_ENV[$key] ?? getenv($key);
    }

    /**
     * 'get' method is used for getting environment variables, and then base64 decodes them,
     * and then converts them from json objects to PHP arrays.
     * returns $default argument if not found.
     *
     * @param string $key
     * @param string|int|null $default
     * @return array|string|int|null
     */
    public function get(string $key, $default = null)
    {
        $envVarName = $this->getEnvironmentVariableName($key);
        $value = $this->getEnv($envVarName);
        if (false === $value) {
            return $default;
        }

        return json_decode(base64_decode($value), true);
    }

    /**
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRoutes(): array
    {
        if (isset($this->data['routes'])) {
            return $this->data['routes'];
        }

        return $this->data['routes'] = $this->get(SystemConfigInterface::VAR_ENV_ROUTES, []);
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return array
     */
    public function getRelationships(): array
    {
        if (isset($this->data['relationships'])) {
            return $this->data['relationships'];
        }

        return $this->data['relationships'] = $this->get(SystemConfigInterface::VAR_ENV_RELATIONSHIPS, []);
    }

    /**
     * Get relationship information from MagentoCloud environment variable by key.
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
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return array
     */
    public function getVariables(): array
    {
        if (isset($this->data['variables'])) {
            return $this->data['variables'];
        }

        return $this->data['variables'] = $this->get(SystemConfigInterface::VAR_ENV_VARIABLES, []);
    }

    /**
     * @return array
     */
    public function getApplication(): array
    {
        if (isset($this->data['application'])) {
            return $this->data['application'];
        }

        return $this->data['application'] = $this->get(SystemConfigInterface::VAR_ENV_APPLICATION, []);
    }

    /**
     * Get environment variable name from .magento.env.yaml configuration file
     * @param string $name
     * @return string
     */
    protected function getEnvironmentVariableName(string $name): string
    {
        return $this->systemConfig->get($name);
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
    public function getAdminLocale(): string
    {
        return $this->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @return string
     */
    public function getAdminUsername(): string
    {
        return $this->getVariables()['ADMIN_USERNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getAdminFirstname(): string
    {
        return $this->getVariables()['ADMIN_FIRSTNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getAdminLastname(): string
    {
        return $this->getVariables()['ADMIN_LASTNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        return $this->getVariables()['ADMIN_EMAIL'] ?? '';
    }

    /**
     * @return string
     */
    public function getAdminPassword(): string
    {
        return $this->getVariables()['ADMIN_PASSWORD'] ?? '';
    }

    /**
     * @return string
     */
    public function getAdminUrl(): string
    {
        return $this->getVariables()['ADMIN_URL'] ?? '';
    }

    /**
     * @return string
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
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
        return isset($_ENV['MAGENTO_CLOUD_ENVIRONMENT'])
            && preg_match(self::GIT_MASTER_BRANCH_RE, $_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
    }
}
