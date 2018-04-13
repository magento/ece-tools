<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->flagManager = $flagManager;
    }

    /**
     * @param string $key
     * @param string|int|null $default
     * @return array|string|int|null
     */
    public function getEnv(string $key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $value = getenv($key);
        if (false === $value) {
            return $default;
        }
        return $value;
    }

    /**
     * @param string $key
     * @param string|int|null $default
     * @return array|string|int|null
     */
    public function get(string $key, $default = null)
    {
        $value = $this->getEnv($key);
        if (null === $value) {
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

        return $this->data['routes'] = $this->get('MAGENTO_CLOUD_ROUTES', []);
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

        return $this->data['relationships'] = $this->get('MAGENTO_CLOUD_RELATIONSHIPS', []);
    }

    /**
     * Get relationship information from MagentoCloud environment variable by key.
     *
     * @param string $key
     * @return array
     */
    public function getRelationship(string $key)
    {
        $relationships = $this->getRelationships();

        return isset($relationships[$key]) ? $relationships[$key] : [];
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

        return $this->data['variables'] = $this->get('MAGENTO_CLOUD_VARIABLES', []);
    }

    /**
     * @return array
     */
    public function getApplication(): array
    {
        if (isset($this->data['application'])) {
            return $this->data['application'];
        }

        return $this->data['application'] = $this->get('MAGENTO_CLOUD_APPLICATION', []);
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
     * @return bool
     */
    public function isDeployStaticContent(): bool
    {
        return !$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
    }

    /**
     * @return string
     */
    public function getAdminLocale(): string
    {
        return $this->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @return string|float
     */
    public function getDbHost()
    {
        return $this->getRelationship('database')[0]['host'] ?? '';
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->getRelationship('database')[0]['path'] ?? '';
    }

    /**
     * @return string
     */
    public function getDbUser(): string
    {
        return $this->getRelationship('database')[0]['username'] ?? '';
    }

    /**
     * @return string
     */
    public function getDbPassword(): string
    {
        return $this->getRelationship('database')[0]['password'] ?? '';
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
