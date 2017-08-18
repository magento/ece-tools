<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const STATIC_CONTENT_DEPLOY_FLAG = '.static_content_deploy';
    const REGENERATE_FLAG = MAGENTO_ROOT . 'var/.regenerate';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    const GIT_MASTER_BRANCH_RE = '/^master(?:-[a-z0-9]+)?$/i';

    const CLOUD_MODE_ENTERPRISE = 'enterprise';

    public $writableDirs = ['var', 'app/etc', 'pub/media'];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     */
    public function __construct(LoggerInterface $logger, File $file)
    {
        $this->logger = $logger;
        $this->file = $file;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return array
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
    public function getRoutes()
    {
        return $this->get('MAGENTO_CLOUD_ROUTES');
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return $this->get('MAGENTO_CLOUD_RELATIONSHIPS');
    }

    /**
     * Get relationship information from MagentoCloud environment variable by key.
     *
     * @param string $key
     * @return array
     */
    public function getRelationship($key)
    {
        $relationships = $this->getRelationships();

        return isset($relationships[$key]) ? $relationships[$key] : [];
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getVariables()
    {
        return $this->get('MAGENTO_CLOUD_VARIABLES');
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
        $this->logger->info('Setting flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        $this->file->touch(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }

    /**
     * Removes flag that static content was generated in build phase.
     *
     * @return void
     */
    public function removeFlagStaticContentInBuild()
    {
        if ($this->isStaticDeployInBuild()) {
            $this->logger->info('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
            $this->file->deleteFile(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        }
    }

    /**
     * Checks if static content generates during build process.
     *
     * @return bool
     */
    public function isStaticDeployInBuild(): bool
    {
        return $this->file->isExists(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }

    /**
     * Retrieves writable directories.
     *
     * @return array
     */
    public function getWritableDirectories(): array
    {
        return $this->writableDirs;
    }

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

    public function getAdminLocale(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_LOCALE']) ? $var['ADMIN_LOCALE'] : 'en_US';
    }

    public function doCleanStaticFiles(): bool
    {
        $var = $this->getVariables();

        return isset($var['CLEAN_STATIC_FILES']) && $var['CLEAN_STATIC_FILES'] == 'disabled' ? false : true;
    }

    /**
     * @return string
     */
    public function getStaticDeployExcludeThemes(): string
    {
        $var = $this->getVariables();

        return isset($var['STATIC_CONTENT_EXCLUDE_THEMES']) ? $var['STATIC_CONTENT_EXCLUDE_THEMES'] : '';
    }

    public function getDbHost()
    {
        return $this->getRelationship('database')[0]['host'];
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->getRelationship('database')[0]['path'];
    }

    /**
     * @return string
     */
    public function getDbUser(): string
    {
        return $this->getRelationship('database')[0]['username'];
    }

    /**
     * @return string
     */
    public function getDbPassword(): string
    {
        return $this->getRelationship('database')[0]['password'];
    }

    /**
     * @return string
     */
    public function getAdminUsername(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_USERNAME']) ? $var['ADMIN_USERNAME'] : 'admin';
    }

    /**
     * @return string
     */
    public function getAdminFirstname(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_FIRSTNAME']) ? $var['ADMIN_FIRSTNAME'] : 'John';
    }

    /**
     * @return string
     */
    public function getAdminLastname(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_LASTNAME']) ? $var['ADMIN_LASTNAME'] : 'Doe';
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_EMAIL']) ? $var['ADMIN_EMAIL'] : 'john@example.com';
    }

    /**
     * @return string
     */
    public function getAdminPassword(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_PASSWORD']) ? $var['ADMIN_PASSWORD'] : 'admin12';
    }

    /**
     * @return string
     */
    public function getAdminUrl(): string
    {
        $var = $this->getVariables();

        return isset($var['ADMIN_URL']) ? $var['ADMIN_URL'] : 'admin';
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
