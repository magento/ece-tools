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
    // core flags
    const MAINTENANCE_FLAG = 'var/.maintenance.flag';
    const REGENERATE_FLAG = 'var/.regenerate';

    // cloud specific flags
    const STATIC_CONTENT_DEPLOY_FLAG = 'var/cloud_flags/static_content_deploy';
    const PRE_START_FLAG = 'var/cloud_flags/prestart_in_progress';
    const DEPLOY_READY_FLAG = 'var/cloud_flags/deploy_ready';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    const GIT_MASTER_BRANCH_RE = '/^master(?:-[a-z0-9]+)?$/i';

    const CLOUD_MODE_ENTERPRISE = 'enterprise';

    const VAL_ENABLED = 'enabled';
    const VAL_DISABLED = 'disabled';

    const DEFAULT_DIRECTORY_MODE = 0755;
    const DEFAULT_FILE_MODE = 0644;


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
    private $restorableDirectories = [
        'static' => 'pub/static',
        'etc' => 'app/etc',
        'media' => 'pub/media',
        'log' => 'var/log',
        'cloud_flags' => 'var/cloud_flags',
    ];

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
     * Test if env var is set to disabled
     *
     * @param string $name Variable to evaluate
     * @return bool
     */
    public function isVariableDisabled($name): bool
    {
        $var = $this->getVariables();

        return (isset($var[$name]) && $var[$name] === 'disabled') ? true : false;
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
        $this->setFlag(static::STATIC_CONTENT_DEPLOY_FLAG);
    }

    /**
     * Removes flag that static content was generated in build phase.
     *
     * @return void
     */
    public function removeFlagStaticContentInBuild()
    {
        $this->clearFlag(static::STATIC_CONTENT_DEPLOY_FLAG);
    }

    /**
     * Checks if static content generates during build process.
     *
     * @return bool
     */
    public function isStaticDeployInBuild(): bool
    {
        return $this->hasFlag(static::STATIC_CONTENT_DEPLOY_FLAG);
    }

    /**
     * Retrieves restorable directories.
     *
     * @return array
     */
    public function getRestorableDirectories(): array
    {
        return $this->restorableDirectories;
    }

    /**
     * @return bool
     */
    public function isDeployStaticContent(): bool
    {
        /**
         * Can use environment variable to always disable.
         * Default is to deploy static content if it was not deployed in the build step.
         */
        if ($this->isVariableDisabled('DO_DEPLOY_STATIC_CONTENT')) {
            $this->logger->info("Static content deploy disabled by environment variable");
            return false;
        }
        return !$this->hasFlag(Environment::STATIC_CONTENT_DEPLOY_FLAG);
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

    /**
     * Checks for presence of a flag
     *
     * @param string $path Path relative to app root
     * @return bool
     */
    public function hasFlag(string $path): bool
    {
        return $this->file->isExists($this->directoryList->getMagentoRoot() . '/' . $path);
    }

    /**
     * Sets a flag
     *
     * @param string $path relative to app root
     * @return bool
     */
    public function setFlag($path): bool
    {
        $flag = $this->directoryList->getMagentoRoot() . '/' . $path;
        if ($this->file->touch($flag)) {
            $this->logger->info("Set flag: $flag");
            return true;
        }
        return false;
    }

    /**
     * Clears a flag
     *
     * @param string $path Path relative to app root
     * @return bool
     */
    public function clearFlag($path): bool
    {
        $flag = $this->directoryList->getMagentoRoot() . '/' . $path;
        if (!$this->file->isExists($flag)) {
            $this->logger->info("$flag already removed");
            return true;
        }
        if ($this->file->deleteFile($flag)) {
            $this->logger->info("Deleted flag: $flag");
            return true;
        }
        return false;
    }

     /**
      * Symlink directory Contents
      *
      * @param string $target path to be linked.
      * @param string $link path to symlink relative to app root.
      * @result void
      */
    public function symlinkDirectoryContents(string $targetDir, string $linkDir)
    {
        foreach ($this->file->readDirectory($targetDir) as $target) {
            $link = "$linkDir/". basename($target);
            if ($this->file->symlink($target, $link)) {
                $this->logger->info("Symlinked $link to $target");
            }
        }
    }
}
