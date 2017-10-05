<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const STATIC_CONTENT_DEPLOY_FLAG = '.static_content_deploy';
    const REGENERATE_FLAG = 'var/.regenerate';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    const GIT_MASTER_BRANCH_RE = '/^master(?:-[a-z0-9]+)?$/i';

    const CLOUD_MODE_ENTERPRISE = 'enterprise';

    const VAL_ENABLED = 'enabled';
    const VAL_DISABLED = 'disabled';

    const DEFAULT_ADMIN_URL = 'admin';
    const DEFAULT_ADMIN_NAME = 'admin';
    const DEFAULT_ADMIN_FIRSTNAME = 'Admin';
    const DEFAULT_ADMIN_LASTNAME = 'Username';

    /**
     * Let's keep variable names same for both phases.
     */
    const VAR_SCD_STRATEGY = Build::OPT_SCD_STRATEGY;

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
     * @var array
     */
    private $data = [];

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(LoggerInterface $logger, File $file, DirectoryList $directoryList)
    {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $key
     * @param string|int|null $default
     * @return array|string|int|null
     */
    public function get(string $key, $default = null)
    {
        return isset($_ENV[$key]) ? json_decode(base64_decode($_ENV[$key]), true) : $default;
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
     * Checks that static content symlink is on.
     *
     * If STATIC_CONTENT_SYMLINK == disabled return false
     * Returns true by default
     *
     * @return bool
     */
    public function isStaticContentSymlinkOn(): bool
    {
        $var = $this->getVariables();

        return isset($var['STATIC_CONTENT_SYMLINK']) && $var['STATIC_CONTENT_SYMLINK'] == 'disabled' ? false : true;
    }

    /**
     * @return string
     */
    public function getVerbosityLevel(): string
    {
        $var = $this->getVariables();

        return isset($var['VERBOSE_COMMANDS']) && $var['VERBOSE_COMMANDS'] == 'enabled' ? ' -vvv ' : '';
    }

    /**
     * @return string
     */
    public function getApplicationMode(): string
    {
        $var = $this->getVariables();
        $mode = isset($var['APPLICATION_MODE']) ? $var['APPLICATION_MODE'] : false;

        return in_array($mode, [self::MAGENTO_DEVELOPER_MODE, self::MAGENTO_PRODUCTION_MODE])
            ? $mode
            : self::MAGENTO_PRODUCTION_MODE;
    }

    /**
     * Sets flag that static content was generated in build phase.
     *
     * @return void
     */
    public function setFlagStaticDeployInBuild()
    {
        $this->logger->info('Setting flag file ' . static::STATIC_CONTENT_DEPLOY_FLAG);
        $this->file->touch(
            $this->directoryList->getMagentoRoot() . '/' . static::STATIC_CONTENT_DEPLOY_FLAG
        );
    }

    /**
     * Removes flag that static content was generated in build phase.
     *
     * @return void
     */
    public function removeFlagStaticContentInBuild()
    {
        if ($this->isStaticDeployInBuild()) {
            $this->logger->info('Removing flag file ' . static::STATIC_CONTENT_DEPLOY_FLAG);
            $this->file->deleteFile(
                $this->directoryList->getMagentoRoot() . '/' . static::STATIC_CONTENT_DEPLOY_FLAG
            );
        }
    }

    /**
     * Checks if static content generates during build process.
     *
     * @return bool
     */
    public function isStaticDeployInBuild(): bool
    {
        return $this->file->isExists(
            $this->directoryList->getMagentoRoot() . '/' . static::STATIC_CONTENT_DEPLOY_FLAG
        );
    }

    /**
     * Retrieves writable directories.
     *
     * @return array
     */
    public function getWritableDirectories(): array
    {
        return ['var', 'app/etc', 'pub/media'];
    }

    /**
     * Retrieves recoverable directories.
     *
     * @return array
     */
    public function getRecoverableDirectories(): array
    {
        return ['var/log', 'app/etc', 'pub/media'];
    }

    /**
     * @return bool
     */
    public function isDeployStaticContent(): bool
    {
        $var = $this->getVariables();

        /**
         * Can use environment variable to always disable.
         * Default is to deploy static content if it was not deployed in the build step.
         */
        if (isset($var['DO_DEPLOY_STATIC_CONTENT']) && $var['DO_DEPLOY_STATIC_CONTENT'] == 'disabled') {
            $flag = false;
        } else {
            $flag = !$this->isStaticDeployInBuild();
        }

        $this->logger->info('Flag DO_DEPLOY_STATIC_CONTENT is set to ' . ($flag ? 'enabled' : 'disabled'));

        return $flag;
    }

    /**
     * @return int
     */
    public function getStaticDeployThreadsCount(): int
    {
        /**
         * Use 1 for PAAS environment.
         */
        $staticDeployThreads = 1;
        $var = $this->getVariables();

        if (isset($var['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$var['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$_ENV['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['MAGENTO_CLOUD_MODE']) && $_ENV['MAGENTO_CLOUD_MODE'] === static::CLOUD_MODE_ENTERPRISE) {
            $staticDeployThreads = 3;
        }

        return $staticDeployThreads;
    }

    /**
     * @return string
     */
    public function getAdminLocale(): string
    {
        return $this->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @return bool
     */
    public function doCleanStaticFiles(): bool
    {
        $var = $this->getVariables();

        return !(isset($var['CLEAN_STATIC_FILES']) && $var['CLEAN_STATIC_FILES'] === static::VAL_DISABLED);
    }

    /**
     * @return string
     */
    public function getStaticDeployExcludeThemes(): string
    {
        return $this->getVariable('STATIC_CONTENT_EXCLUDE_THEMES', '');
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
     * @return bool
     */
    public function isUpdateUrlsEnabled(): bool
    {
        $var = $this->getVariables();

        return isset($var['UPDATE_URLS']) && $var['UPDATE_URLS'] == 'disabled' ? false : true;
    }

    /**
     * @return string
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
    }

    /**
     * @return bool
     */
    public function isMasterBranch(): bool
    {
        return isset($_ENV['MAGENTO_CLOUD_ENVIRONMENT'])
            && preg_match(self::GIT_MASTER_BRANCH_RE, $_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
    }
}
