<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;

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

    const VAL_ENABLED = 'enabled';
    const VAL_DISABLED = 'disabled';

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
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(LoggerInterface $logger, File $file, DirectoryList $directoryList, DeployConfig $deployConfig, PasswordGenerator $passwordGenerator)
    {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->deployConfig = $deployConfig;
        $this->passwordGenerator = $passwordGenerator;
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
        return $this->get('MAGENTO_CLOUD_ROUTES', []);
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
    public function getRelationship(string $key)
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
        return $this->getVariables()['STATIC_CONTENT_EXCLUDE_THEMES'] ?? '';
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
        $var = $this->getVariables();
        if (!empty($var['ADMIN_USERNAME'])) {
            return $var['ADMIN_USERNAME'];
        }
        if (!$this->deployConfig->isInstalling()) {
            return "";
        }
        // TODO: We want to have a random username , but because the username is not sent in the reset password email, the new admin has no way of knowing what it is at the moment.
        //       We may either make a custom email template to do this, or find a different way to do this.  Then, we can use random a username.
        // return "admin-" . Password::generateRandomString(6);
        return "admin";
    }

    /**
     * @return string
     */
    public function getAdminFirstname(): string
    {
        $var = $this->getVariables();
        return !empty($var["ADMIN_FIRSTNAME"]) ? $var["ADMIN_FIRSTNAME"] : ($this->deployConfig->isInstalling() ? "Changeme" : "");
    }

    /**
     * @return string
     */
    public function getAdminLastname(): string
    {
        $var = $this->getVariables();
        return !empty($var["ADMIN_LASTNAME"]) ? $var["ADMIN_LASTNAME"] : ($this->deployConfig->isInstalling() ? "Changeme" : "");
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        $var = $this->getVariables();
        /*   Note: We are going to have the onboarding process set the ADMIN_EMAIL variables to the email address specified during
         * the project creation.  This will let us do the reset password for the new installs. */
        if (!empty($var["ADMIN_EMAIL"])) {
            return $var["ADMIN_EMAIL"];
        }
        if ($this->deployConfig->isInstalling() /* && empty($var["ADMIN_PASSWORD"])*/) {
            // Note: I didn't want to throw exception here if ADMIN_PASSWORD is set... but bin/magento setup:install fails if --admin-email is blank, so it's better to die with a useful error message
            // Note: not relying on bin/magento because it might not be working at this point.
            //    $this->env->execute('touch ' . realpath(Environment::MAGENTO_ROOT . 'var') . '/.maintenance.flag');
            $this->logger->error("ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email.  Please set ADMIN_EMAIL and retry deploy.");
            throw new \RuntimeException("ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email.  Please set ADMIN_EMAIL and retry deploy.");
        }
        return "";
    }

    private $adminPassword = null;  // Note: If we are generating a random password, we need to cache it so we don't return a new random one each time.

    /**
     * @return string
     */
    public function getAdminPassword(): string
    {
        if (is_null($this->adminPassword)) {
            $var = $this->getVariables();
            if (!isEmpty($var['ADMIN_PASSWORD'])) {
                $this->adminPassword = $var['ADMIN_PASSWORD'];
            } else {
                if (!$this->deployConfig->isInstalling()) {
                    $this->adminPassword = "";
                } else {
                    $this->adminPassword = generateRandomPassword();
                }
            }
        }
        return $this->adminPassword;
    }

    /**
     * @return string
     */
    public function getAdminUrl(): string
    {
        $var = $this->getVariables();
        /* Note: ADMIN_URL should be set during the onboarding process also.  They should have generated a random one for us to use. */
        //$this->adminUrl = isset($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->isInstalling ? "admin_" . Password::generateRandomString(8) : "");
        /* Note: We are defaulting to "admin" for now, but will change it to the above random admin URL at some point */
        return !empty($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->deployConfig->isInstalling() ? "admin" : "");
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
