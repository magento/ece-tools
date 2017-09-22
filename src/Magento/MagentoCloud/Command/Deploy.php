<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Database;
use Magento\MagentoCloud\Password;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{
    const MAGIC_ROUTE = '{default}';

    const PREFIX_SECURE = 'https://';
    const PREFIX_UNSECURE = 'http://';

    const GIT_MASTER_BRANCH_RE = '/^master(?:-[a-z0-9]+)?$/i';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    private $urls = ['unsecure' => [], 'secure' => []];

    private $defaultCurrency = 'USD';

    private $amqpHost;
    private $amqpPort;
    private $amqpUser;
    private $amqpPasswd;
    private $amqpVirtualhost = '/';
    private $amqpSsl = '';

    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    private $adminUsername;
    private $adminFirstname;
    private $adminLastname;
    private $adminEmail;
    private $adminPassword;
    private $adminUrl;
    private $enableUpdateUrls;

    private $redisHost;
    private $redisPort;
    private $redisSessionDb = '0';
    private $redisCacheDb = '1'; // Value hard-coded in pre-deploy.php

    private $isMasterBranch = null;
    private $magentoApplicationMode;
    private $cleanStaticViewFiles;
    private $staticDeployThreads;
    private $staticDeployExcludeThemes = [];
    private $adminLocale;
    private $doDeployStaticContent;

    private $verbosityLevel;
    private $isInstalling;
    /** @var Database|null This our connection to the database we use to execute queries. */
    private $database;

    /**
     * @var Environment
     */
    private $env;

    public function __construct()
    {
        $this->loadEnvironmentData();
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploys application');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preDeploy();
        $this->env->log("Starting deploy.");
        $this->createConfigIfNotYetExist();
        $this->processMagentoMode();
        if ($this->isInstalling) {
            $this->installMagento();
        } else {
            $this->updateMagento();
        }
        $this->staticContentDeploy();
        $this->disableGoogleAnalytics();
        if ($this->isInstalling) {
            $this->sendPasswordResetEmail();
        }
        $this->env->log("Deployment complete.");
    }

    /**
     * Create config file if it doesn't yet exist.
     */
    private function createConfigIfNotYetExist()
    {
        $configFile = $this->getEnvConfigFilePath();
        if (file_exists($configFile)) {
            return;
        }
        //$this->env->execute('touch ' . Environment::MAGENTO_ROOT . $configFile);
        $updatedConfig = '<?php' . "\n" . 'return array();';
        file_put_contents($configFile, $updatedConfig);
    }

    /**
     * Parse and save information about environment configuration and variables.
     */
    private function loadEnvironmentData()
    {
        $this->env = new Environment();
        $this->env->log("Preparing environment specific data.");

        $this->initRoutes();

        $relationships = $this->env->getRelationships();
        $var = $this->env->getVariables();

        $this->dbHost = $relationships["database"][0]["host"];
        $this->dbName = $relationships["database"][0]["path"];
        $this->dbUser = $relationships["database"][0]["username"];
        $this->dbPassword = $relationships["database"][0]["password"];

        $this->createDatabaseConnection();  // Note: We have to create the database here, after we get the $relationships values, but before isInstalled() which uses the database
        $this->isInstalling = !$this->isInstalled();

        /* Note: Moved the admin variables to their own function to help with MAGECLOUD-115 and MAGECLOUD-894 */
        $this->loadEnvironmentDataForAdmin($var);

        $this->enableUpdateUrls = isset($var["UPDATE_URLS"]) && $var["UPDATE_URLS"] == 'disabled' ? false : true;
        $this->cleanStaticViewFiles = isset($var["CLEAN_STATIC_FILES"]) && $var["CLEAN_STATIC_FILES"] == 'disabled'
            ? false : true;
        $this->staticDeployExcludeThemes = isset($var["STATIC_CONTENT_EXCLUDE_THEMES"])
            ? $var["STATIC_CONTENT_EXCLUDE_THEMES"] : [];
        $this->adminLocale = isset($var["ADMIN_LOCALE"]) ? $var["ADMIN_LOCALE"] : "en_US";

        if (isset($var["STATIC_CONTENT_THREADS"])) {
            $this->staticDeployThreads = (int)$var["STATIC_CONTENT_THREADS"];
        } elseif (isset($_ENV["STATIC_CONTENT_THREADS"])) {
                $this->staticDeployThreads = (int)$_ENV["STATIC_CONTENT_THREADS"];
        } elseif (isset($_ENV["MAGENTO_CLOUD_MODE"]) && $_ENV["MAGENTO_CLOUD_MODE"] === 'enterprise') {
            $this->staticDeployThreads = 3;
        } else { // if Paas environment
            $this->staticDeployThreads = 1;
        }

        $this->doDeployStaticContent =
            isset($var["DO_DEPLOY_STATIC_CONTENT"]) && $var["DO_DEPLOY_STATIC_CONTENT"] == 'disabled' ? false : true;
        /**
         * Can use environment variable to always disable.
         * Default is to deploy static content if it was not deployed in the build step.
         */
        if (isset($var["DO_DEPLOY_STATIC_CONTENT"]) && $var["DO_DEPLOY_STATIC_CONTENT"] == 'disabled') {
            $this->doDeployStaticContent = false;
            $this->env->log(' Flag DO_DEPLOY_STATIC_CONTENT is set to disabled');
        } else {
            $this->doDeployStaticContent = !$this->env->isStaticDeployInBuild();
            $this->env->log(' Flag DO_DEPLOY_STATIC_CONTENT is set to ' . $this->doDeployStaticContent);
        }

        $this->magentoApplicationMode = isset($var["APPLICATION_MODE"]) ? $var["APPLICATION_MODE"] : false;
        $this->magentoApplicationMode =
            in_array($this->magentoApplicationMode, array(self::MAGENTO_DEVELOPER_MODE, self::MAGENTO_PRODUCTION_MODE))
                ? $this->magentoApplicationMode
                : self::MAGENTO_PRODUCTION_MODE;

        if (isset($relationships['redis']) && count($relationships['redis']) > 0) {
            $this->redisHost = $relationships['redis'][0]['host'];
            $this->redisPort = $relationships['redis'][0]['port'];
        }

        if (isset($relationships["mq"]) && count($relationships['mq']) > 0) {
            $this->amqpHost = $relationships["mq"][0]["host"];
            $this->amqpUser = $relationships["mq"][0]["username"];
            $this->amqpPasswd = $relationships["mq"][0]["password"];
            $this->amqpPort = $relationships["mq"][0]["port"];
        }

        $this->verbosityLevel = isset($var['VERBOSE_COMMANDS']) && $var['VERBOSE_COMMANDS'] == 'enabled'
            ? ' -vvv ' : '';
    }

    /**
     * Load the admin settings from the environment.
     * @param array $var
     */
    private function loadEnvironmentDataForAdmin($var = null)
    {
        if (is_null($var)) {
            $var = $this->env->getVariables();
        }
        /* We no longer set default username/password.  If we are installing, we will use random username/password.*/
        $this->adminUsername = isset($var["ADMIN_USERNAME"]) ? $var["ADMIN_USERNAME"] : "";
        $this->adminPassword = isset($var["ADMIN_PASSWORD"]) ? $var["ADMIN_PASSWORD"] : "";
        if ($this->isInstalling && empty($this->adminUsername)) {
            // TODO: We want to have a random username , but because the username is not sent in the reset password email, the new admin has no way of knowing what it is at the moment.
            //       We may either make a custom email template to do this, or find a different way to do this.  Then, we can use random a username.
            //$this->adminUsername = "admin-" . Password::generateRandomString(6);
            $this->adminUsername = "admin";
        }
        if ($this->isInstalling && empty($this->adminPassword)) {
            $this->adminPassword = Password::generateRandomPassword();
        }
        $this->adminFirstname = isset($var["ADMIN_FIRSTNAME"]) ? $var["ADMIN_FIRSTNAME"] : ($this->isInstalling ? "Changeme" : "");
        $this->adminLastname = isset($var["ADMIN_LASTNAME"]) ? $var["ADMIN_LASTNAME"] : ($this->isInstalling ? "Changeme" : "");
        /*   Note: We are going to have the onboarding process set the ADMIN_EMAIL variables to the email address specified during
         * the project creation.  This will let us do the reset password for the new installs. */
        if (isset($var["ADMIN_EMAIL"])) {
            $this->adminEmail = $var["ADMIN_EMAIL"];
        } else {
            if ($this->isInstalling /* && empty($var["ADMIN_PASSWORD"])*/) {
                // Note: I didn't want to throw exception here if ADMIN_PASSWORD is set... but bin/magento setup:install fails if --admin-email is blank, so it's better to die with a useful error message
                // Note: not relying on bin/magento because it might not be working at this point.
                $this->env->execute('touch ' . realpath(Environment::MAGENTO_ROOT . 'var') . '/.maintenance.flag');
                throw new \RuntimeException("ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email.  Please set ADMIN_EMAIL and retry deploy.");
            } else {
                $this->adminEmail = "";
            }
        }
        /* Note: ADMIN_URL should be set during the onboarding process also.  They should have generated a random one for us to use. */
        //$this->adminUrl = isset($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->isInstalling ? "admin_" . Password::generateRandomString(8) : "");
        /* Note: We are defaulting to "admin" for now, but will change it to the above random admin URL at some point */
        $this->adminUrl = isset($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->isInstalling ? "admin" : "");
    }

    /**
     * Create the database connection;
     */
    private function createDatabaseConnection()
    {
        $this->database = new Database($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * @return bool
     */
    public function isInstalled()
    {
        $configFile = $this->getEnvConfigFilePath();
        $installed = false;

        //1. from environment variables check if db exists and has tables
        //2. check if core_config_data and setup_module tables exist
        //3. check install date

        $this->env->log('Checking if db exists and has tables');
        $output = $this->database->executeDbQuery('SHOW TABLES', [], MYSQLI_NUM);
        $output = array_map(function ($arrayin) {
            return $arrayin[0];
        }, $output);
        if (is_array($output) && count($output) > 0) {
            if (!in_array('core_config_data', $output) || !in_array('setup_module', $output)) {
                $this->env->log('Missing either core_config_data or setup_module table');
                exit(5);
            } elseif (file_exists($configFile)) {
                $data = include $configFile;
                if (isset($data['install']) && isset($data['install']['date'])) {
                    $this->env->log("Magento was installed on " . $data['install']['date']);
                    $installed = true;
                } else {
                    $config['install']['date'] = date('r');
                    $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
                    file_put_contents($configFile, $updatedConfig);
                    $installed = true;
                }
            } else {
                $this->env->execute('touch ' . Environment::MAGENTO_ROOT . $configFile);
                $config['install']['date'] = date('r');
                $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
                file_put_contents($configFile, $updatedConfig);
                $installed = true;
            }
        }
        return $installed;
    }

    /**
     * Run Magento installation
     */
    public function installMagento()
    {
        $this->env->log("Installing Magento.");

        $urlUnsecure = $this->urls['unsecure'][''];
        $urlSecure = $this->urls['secure'][''];

        $command =
            "php ./bin/magento setup:install"
            . " " . escapeshellarg("--session-save=db")
            . " " . escapeshellarg("--cleanup-database")
            . " " . escapeshellarg("--currency=$this->defaultCurrency")
            . " " . escapeshellarg("--base-url=$urlUnsecure")
            . " " . escapeshellarg("--base-url-secure=$urlSecure")
            . " " . escapeshellarg("--language=$this->adminLocale")
            . " " . escapeshellarg("--timezone=America/Los_Angeles")
            . " " . escapeshellarg("--db-host=$this->dbHost")
            . " " . escapeshellarg("--db-name=$this->dbName")
            . " " . escapeshellarg("--db-user=$this->dbUser")
            . " " . escapeshellarg("--backend-frontname=$this->adminUrl")
            . " " . escapeshellarg("--admin-user=$this->adminUsername")
            . " " . escapeshellarg("--admin-firstname=$this->adminFirstname")
            . " " . escapeshellarg("--admin-lastname=$this->adminLastname")
            . " " . escapeshellarg("--admin-email=$this->adminEmail")
            . " " . escapeshellarg("--admin-password=" . Password::generateRandomPassword()); // Note: This password gets changed later in this script in updateAdminCredentials

        if (strlen($this->dbPassword)) {
            $command .= " " . escapeshellarg("--db-password=$this->dbPassword");
        }

        $command .= $this->verbosityLevel;

        $this->env->execute($command);

        $this->setSecureAdmin();
        $this->updateConfig();
        $this->importDeploymentConfig();
    }

    /**
     * Update secure admin
     */
    public function setSecureAdmin()
    {
        $this->env->log("Setting secure admin");
        $secPath = 'web/secure/use_in_adminhtml';
        if (empty($this->database->executeDbQuery("SELECT * FROM core_config_data WHERE path='$secPath';", [], MYSQLI_ASSOC))) {
            $this->database->executeDbQuery("INSERT INTO core_config_data (scope, scope_id, path, value) VALUES('default', '0', '$secPath', '1');");
        } else {
            $this->database->executeDbQuery("UPDATE core_config_data SET value = '1' WHERE path = '$secPath';");
        }
    }

    /**
     * Import deployment config - To be made obsolete by MAGETWO-71890
     *
     * @return void
     */
    public function importDeploymentConfig()
    {
        $this->env->log("Importing deployment config");
        $this->env->execute("php ./bin/magento app:config:import {$this->verbosityLevel}");
    }

    /**
     * Update Magento configuration
     */
    private function updateMagento()
    {
        $this->env->log("File env.php contains installation date. Updating configuration.");
        $this->updateConfig();
        $this->setupUpgrade();
        $this->clearCache();
    }

    private function updateConfig()
    {
        $this->env->log("Updating configuration from environment variables.");
        $this->updateConfiguration();
        $this->updateAdminCredentials();
        $this->updateUrls();
    }

    /**
     * Update admin credentials
     */
    private function updateAdminCredentials()
    {
        // Old query for reference: "UPDATE admin_user SET firstname = ?, lastname = ?, email = ?, username = ?, password = ? WHERE user_id = '1';"
        $parameters = [""];
        $query = "";
        $addColumnValueToBeUpdated = function ($value, &$query, $columnName, $valueType, &$parameters, $value2 = null) {
            if (!empty($value)) {
                if (!empty($query)) {
                    $query .= ",";
                }
                $query .= " $columnName = ? ";
                $parameters[0] .= $valueType;
                $parameters[] = $value2 ?: $value;
            }
        };
        $addColumnValueToBeUpdated($this->adminFirstname, $query, "firstname", "s", $parameters);
        $addColumnValueToBeUpdated($this->adminLastname, $query, "lastname", "s", $parameters);
        $addColumnValueToBeUpdated($this->adminEmail, $query, "email", "s", $parameters);
        $addColumnValueToBeUpdated($this->adminUsername, $query, "username", "s", $parameters);
        $addColumnValueToBeUpdated($this->adminPassword, $query, "password", "s", $parameters, Password::generatePassword($this->adminPassword));
        if (empty($query)) {
            return;  // No variables set ; nothing to do
        }
        $this->env->log("Updating admin credentials.");
        $query = "UPDATE admin_user SET" . $query . "  WHERE user_id = '1';";
        $this->database->executeDbQuery($query, $parameters);
    }

    /**
     * Returns SOLR configuration
     *
     * @param array $config Solr connection configuration
     * @return array
     */
    private function getSolrConfiguration(array $config)
    {
        $this->env->log("Updating SOLR configuration.");
        return [
            'engine' => 'solr',
            'solr_server_hostname' => $config['host'],
            'solr_server_port' => $config['port'],
            'solr_server_username' => $config['scheme'],
            'solr_server_path' => $config['path'],
        ];
    }

    /**
     * Returns ElasticSearch configuration
     *
     * @param array $config Elasticsearch connection configuration
     * @return array
     */
    private function getElasticSearchConfiguration(array $config)
    {
        $this->env->log("Updating elasticsearch configuration.");
        return [
            'engine' => 'elasticsearch',
            'elasticsearch_server_hostname' => $config['host'],
            'elasticsearch_server_port' => $config['port'],
        ];
    }

    /**
     * Returns search engine configuration depends on relationships
     *
     * @return array
     */
    private function getSearchEngineConfiguration()
    {
        $relationships = $this->env->getRelationships();

        if (isset($relationships['elasticsearch'])) {
            $searchConfig = $this->getElasticSearchConfiguration($relationships['elasticsearch'][0]);
        } else if (isset($relationships['solr'])) {
            $searchConfig = $this->getSolrConfiguration($relationships['solr'][0]);
        } else {
            $searchConfig = ['engine' => 'mysql'];
        }

        return $searchConfig;
    }

    /**
     * Update secure and unsecure URLs
     */
    private function updateUrls()
    {
        if ($this->enableUpdateUrls) {
            $this->env->log("Updating secure and unsecure URLs.");
            foreach ($this->urls as $urlType => $urls) {
                foreach ($urls as $route => $url) {
                    $prefix = 'unsecure' === $urlType ? self::PREFIX_UNSECURE : self::PREFIX_SECURE;
                    if (!strlen($route)) {
                    // @codingStandardsIgnoreStart                      
                        $this->database->executeDbQuery("UPDATE core_config_data SET value = ? WHERE path = ? AND scope_id = '0';",
                            ["ss", $url, "web/$urlType/base_url"]);
                      // @codingStandardsIgnoreEnd
                        continue;
                    }
                    $likeKey = $prefix . $route . '%';
                    $likeKeyParsed = $prefix . str_replace('.', '---', $route) . '%';
                    // @codingStandardsIgnoreStart
                    $this->database->executeDbQuery("UPDATE core_config_data SET value = ? WHERE path = ? AND (value LIKE ? OR value LIKE ?);",
                        ["ssss", $url, "web/$urlType/base_url", $likeKey, $likeKeyParsed]);
                    // @codingStandardsIgnoreEnd
                }
            }
        } else {
            $this->env->log("Skipping URL updates");
        }
    }


    /**
     * Run Magento setup upgrade
     */
    private function setupUpgrade()
    {
        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->env->log("Removing .regenerate flag");
            unlink(Environment::REGENERATE_FLAG);
        }

        try {
            /* Enable maintenance mode */
            $this->env->log("Enabling Maintenance mode.");
            $this->env->execute("php ./bin/magento maintenance:enable {$this->verbosityLevel}");

            $this->env->log("Running setup upgrade.");
            $this->env->execute("php ./bin/magento setup:upgrade --keep-generated -n {$this->verbosityLevel}");

            /* Disable maintenance mode */
            $this->env->execute("php ./bin/magento maintenance:disable {$this->verbosityLevel}");
            $this->env->log("Maintenance mode is disabled.");
        } catch (\RuntimeException $e) {
            $this->env->log($e->getMessage());
            //Rollback required by database
            exit(6);
        }
        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->env->log("Removing .regenerate flag");
            unlink(Environment::REGENERATE_FLAG);
        }
    }

    /**
     * Clear Magento file based cache
     */
    private function clearCache()
    {
        $this->env->log("Clearing application cache.");

        $this->env->execute(
            "php ./bin/magento cache:flush {$this->verbosityLevel}"
        );
    }

    /**
     * Update env.php file content
     */
    private function updateConfiguration()
    {
        $this->env->log("Updating env.php database configuration.");

        $configFileName = $this->getEnvConfigFilePath();

        $config = include $configFileName;

        $config['db']['connection']['default']['username'] = $this->dbUser;
        $config['db']['connection']['default']['host'] = $this->dbHost;
        $config['db']['connection']['default']['dbname'] = $this->dbName;
        $config['db']['connection']['default']['password'] = $this->dbPassword;

        $config['db']['connection']['indexer']['username'] = $this->dbUser;
        $config['db']['connection']['indexer']['host'] = $this->dbHost;
        $config['db']['connection']['indexer']['dbname'] = $this->dbName;
        $config['db']['connection']['indexer']['password'] = $this->dbPassword;

        if ($this->amqpHost !== null && $this->amqpPort !== null
            && $this->amqpUser !== null && $this->amqpPasswd !== null) {
            $config['queue']['amqp']['host'] = $this->amqpHost;
            $config['queue']['amqp']['port'] = $this->amqpPort;
            $config['queue']['amqp']['user'] = $this->amqpUser;
            $config['queue']['amqp']['password'] = $this->amqpPasswd;
            $config['queue']['amqp']['virtualhost'] = $this->amqpVirtualhost;
            $config['queue']['amqp']['ssl'] = $this->amqpSsl;
        } else {
            $config = $this->removeAmqpConfig($config);
        }

        if ($this->redisHost !== null && $this->redisPort !== null) {
            $this->env->log("Updating env.php Redis cache configuration.");
            if (empty($config['cache'])) {
                $config['cache'] = $this->getRedisCacheConfiguration();
            } else {
                $config['cache'] = array_replace_recursive($config['cache'], $this->getRedisCacheConfiguration());
            }
            $config['session']['save'] = "redis";
            $config['session']['redis']['host'] = $this->redisHost;
            $config['session']['redis']['port'] = $this->redisPort;
            $config['session']['redis']['database'] = $this->redisSessionDb;
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $config['system']['default']['catalog']['search'] = array_replace_recursive(
            $config['system']['default']['catalog']['search'] ?? [],
            $this->getSearchEngineConfiguration()
        );

        if (!empty($this->adminUrl)) {
            $config['backend']['frontName'] = $this->adminUrl;
        }

        $config['resource']['default_setup']['connection'] = 'default';

        $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';

        file_put_contents($configFileName, $updatedConfig);
    }

    /**
     * Remove AMQP configuration from env.php
     *
     * @param array $config
     * @return array
     */
    private function removeAmqpConfig(array $config)
    {
        $this->env->log("Removing AMQP configuration from env.php.");
        if (isset($config['queue']['amqp'])) {
            if (count($config['queue']) > 1) {
                unset($config['queue']['amqp']);
            } else {
                unset($config['queue']);
            }
        }

        return $config;
    }

    /**
     * If current deploy is about master branch
     *
     * @return boolean
     */
    private function isMasterBranch()
    {
        if (is_null($this->isMasterBranch)) {
            if (isset($_ENV["MAGENTO_CLOUD_ENVIRONMENT"])
                && preg_match(self::GIT_MASTER_BRANCH_RE, $_ENV["MAGENTO_CLOUD_ENVIRONMENT"])
            ) {
                $this->isMasterBranch = true;
            } else {
                $this->isMasterBranch = false;
            }
        }
        return $this->isMasterBranch;
    }

    /**
     * If branch is not master then disable Google Analytics
     */
    private function disableGoogleAnalytics()
    {
        if (!$this->isMasterBranch()) {
            $this->env->log("Disabling Google Analytics");
            $this->database->executeDbQuery("UPDATE core_config_data SET value = 0 WHERE path = 'google/analytics/active';");
        }
    }

    /**
     *  This function deploys the static content.
     *  Moved this from processMagentoMode() to its own function because we changed the order to have
     *  processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     */
    private function staticContentDeploy()
    {
        if ($this->magentoApplicationMode == self::MAGENTO_PRODUCTION_MODE) {
            /* Workaround for MAGETWO-58594: disable redis cache before running static deploy, re-enable after */
            if ($this->doDeployStaticContent) {
                $this->deployStaticContent();
            }
        }
    }

    /**
     * Based on variable APPLICATION_MODE. Production mode by default
     */
    private function processMagentoMode()
    {
        $this->env->log("Set Magento application mode to '{$this->magentoApplicationMode}'");

        /* Enable application mode */
        if ($this->magentoApplicationMode == self::MAGENTO_PRODUCTION_MODE) {
            /** Note: We moved call to deployStaticContent to a new function, staticContentDeploy(),
             * and made it run after production mode is enabled to work around the bug with the read only
             */
            $this->env->log("Enable production mode");
            $configFileName = $this->getEnvConfigFilePath();
            $config = include $configFileName;
            $config['MAGE_MODE'] = 'production';
            $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
            file_put_contents($configFileName, $updatedConfig);
        } else {
            $this->env->log("Enable developer mode");
            $this->env->execute(
                "php ./bin/magento deploy:mode:set " . self::MAGENTO_DEVELOPER_MODE . $this->verbosityLevel
            );
        }
    }

    private function deployStaticContent()
    {
        // Clear old static content if necessary
        if ($this->cleanStaticViewFiles) {
            $this->env->removeStaticContent();
        }
        $this->env->log("Generating fresh static content");
        $this->generateFreshStaticContent();
    }

    private function generateFreshStaticContent()
    {
        $this->env->execute('touch ' . Environment::MAGENTO_ROOT . 'pub/static/deployed_version.txt');
        /* Enable maintenance mode */
        $this->env->log("Enabling Maintenance mode.");
        $this->env->execute("php ./bin/magento maintenance:enable {$this->verbosityLevel}");

        /* Generate static assets */
        $this->env->log("Extract locales");

        $excludeThemesOptions = '';
        if ($this->staticDeployExcludeThemes) {
            $themes = preg_split("/[,]+/", $this->staticDeployExcludeThemes);
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        $jobsOption = $this->staticDeployThreads
            ? "--jobs={$this->staticDeployThreads}"
            : '';

        $locales = implode(' ', $this->getLocales());
        $logMessage = $locales ? "Generating static content for locales: $locales" : "Generating static content.";
        $this->env->log($logMessage);

        $strategy = $this->getScdStrategy();
        $logMessage = $strategy
            ? 'Strategy for generating static content is ' . $strategy
            : 'Default strategy is used for generating static content';
        $this->env->log($logMessage);

        $baseCommand = 'php ./bin/magento setup:static-content:deploy  -f';
        $this->env->execute(
            "$baseCommand $jobsOption $excludeThemesOptions $locales {$this->verbosityLevel} $strategy"
        );

        /* Disable maintenance mode */
        $this->env->execute("php ./bin/magento maintenance:disable {$this->verbosityLevel}");
        $this->env->log("Maintenance mode is disabled.");
    }

    /**
     * Return strategy option for static content deployment.
     * Value is retrieved from SCD_STRATEGY magento environment variable, otherwise returns empty string
     *
     * @return string
     */
    private function getScdStrategy()
    {
        $var = $this->env->getVariables();
        return !empty($var['SCD_STRATEGY']) ? '-s ' . $var['SCD_STRATEGY'] : '';
    }


    /**
     * Gets locales from DB which are set to stores and admin users.
     * Adds additional default 'en_US' locale to result, if it does't exist yet in defined list.
     *
     * @return array List of locales. Returns empty array in case when no locales are defined in DB
     * ```php
     * [
     *     'en_US',
     *     'fr_FR'
     * ]
     * ```
     */
    private function getLocales()
    {
        $locales = [];

        $query = 'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
            . 'UNION SELECT interface_locale FROM admin_user;';
        $output = $this->database->executeDbQuery($query, [], MYSQLI_NUM);
        $output = array_map(function ($arrayin) {
            return $arrayin[0];
        }, $output);
        if (is_array($output) && count($output) > 0) {
            $locales = $output;

            if (!in_array($this->adminLocale, $locales)) {
                $locales[] = $this->adminLocale;
            }
        }
        return $locales;
    }

    /**
     * Parse MagentoCloud routes to more readable format.
     */
    private function initRoutes()
    {
        $this->env->log("Initializing routes.");

        $routes = $this->env->getRoutes();

        foreach ($routes as $key => $val) {
            if ($val["type"] !== "upstream") {
                continue;
            }

            $urlParts = parse_url($val['original_url']);
            $originalUrl = str_replace(self::MAGIC_ROUTE, '', $urlParts['host']);

            if (strpos($key, self::PREFIX_UNSECURE) === 0) {
                $this->urls['unsecure'][$originalUrl] = $key;
                continue;
            }

            if (strpos($key, self::PREFIX_SECURE) === 0) {
                $this->urls['secure'][$originalUrl] = $key;
                continue;
            }
        }

        if (!count($this->urls['secure'])) {
            $this->urls['secure'] = $this->urls['unsecure'];
        }

        $this->env->log(sprintf("Routes: %s", var_export($this->urls, true)));
    }

    private function getRedisCacheConfiguration()
    {
        return [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => $this->redisHost,
                        'port' => $this->redisPort,
                        'database' => $this->redisCacheDb
                    ]
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => $this->redisHost,
                        'port' => $this->redisPort,
                        'database' => $this->redisCacheDb
                    ]
                ]
            ]
        ];
    }

    /**
     * This script contains logic to cleanup outdated caches and restore the contents of mounted directories so that
     * the main deploy hook is able to start.
     */
    private function preDeploy()
    {
        $this->env->log($this->env->startingMessage("pre-deploy"));
        // Clear redis and file caches
        $relationships = $this->env->getRelationships();
        $var = $this->env->getVariables();
        $useStaticContentSymlink = isset($var["STATIC_CONTENT_SYMLINK"]) && $var["STATIC_CONTENT_SYMLINK"] == 'disabled'
            ? false : true;

        if (isset($relationships['redis']) && count($relationships['redis']) > 0) {
            $redisHost = $relationships['redis'][0]['host'];
            $redisPort = $relationships['redis'][0]['port'];
            $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Command\Deploy::$redisCacheDb
            $this->env->execute("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushdb");
        }

        $fileCacheDir = Environment::MAGENTO_ROOT . '/var/cache';
        if (file_exists($fileCacheDir)) {
            $this->env->execute("rm -rf $fileCacheDir");
        }

        $mountedDirectories = ['app/etc', 'pub/media'];

        $buildDir = realpath(Environment::MAGENTO_ROOT . 'init') . '/';

        /**
         * Handle case where static content is deployed during build hook:
         *  1. set a flag to be read by magento-cloud:deploy
         *  2. Either copy or symlink files from init/ directory, depending on strategy
         */
        if (file_exists(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG)) {
            $this->env->log("Static content deployment was performed during build hook");
            $this->env->removeStaticContent();

            if ($useStaticContentSymlink) {
                $this->env->log("Symlinking static content from pub/static to init/pub/static");

                // Symlink pub/static/* to init/pub/static/*
                $staticContentLocation = realpath(Environment::MAGENTO_ROOT . 'pub/static') . '/';
                if (file_exists($buildDir . 'pub/static')) {
                    $dir = new \DirectoryIterator($buildDir . 'pub/static');
                    foreach ($dir as $fileInfo) {
                        $fileName = $fileInfo->getFilename();
                        if (!$fileInfo->isDot()
                            && symlink(
                                $buildDir . 'pub/static/' . $fileName,
                                $staticContentLocation . '/' . $fileName
                            )
                        ) {
                            // @codingStandardsIgnoreStart
                            $this->env->log('Symlinked ' . $staticContentLocation . '/' . $fileName . ' to ' . $buildDir . 'pub/static/' . $fileName);
                            // @codingStandardsIgnoreEnd
                        }
                    }
                }
            } else {
                $this->env->log("Copying static content from init/pub/static to pub/static");
                $this->copyFromBuildDir('pub/static');
            }
        }

        // Restore mounted directories
        $this->env->log("Copying writable directories back.");

        foreach ($mountedDirectories as $dir) {
            $this->copyFromBuildDir($dir);
        }

        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->env->log("Removing var/.regenerate flag");
            unlink(Environment::REGENERATE_FLAG);
        }
    }

    /**
     * @param string $dir The directory to copy. Pass in its normal location relative to Magento root with no prepending
     *                    or trailing slashes
     */
    private function copyFromBuildDir($dir)
    {
        $fullPathDir = Environment::MAGENTO_ROOT . $dir;
        if (!file_exists($fullPathDir)) {
            mkdir($fullPathDir);
            $this->env->log(sprintf('Created directory: %s', $dir));
        }
        $this->env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
        $this->env->log(sprintf('Copied directory: %s', $dir));
    }

    /**
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    private function getEnvConfigFilePath()
    {
        return Environment::MAGENTO_ROOT . 'app/etc/env.php';
    }

    /**
     * Return full path to shared configuration file.
     *
     * @return string The path to configuration file
     */
    private function getSharedConfigFilePath()
    {
        return Environment::MAGENTO_ROOT . 'app/etc/config.php';
    }

    /**
     * Clears configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisConfiguration($config)
    {
        $this->env->log("Removing redis cache and session configuration from env.php.");

        if (isset($config['session']['save']) && $config['session']['save'] == 'redis') {
            $config['session']['save'] = 'db';
            if (isset($config['session']['redis'])) {
                unset($config['session']['redis']);
            }
        }

        if (isset($config['cache']['frontend'])) {
            foreach ($config['cache']['frontend'] as $cacheName => $cacheData) {
                if (isset($cacheData['backend']) && $cacheData['backend'] == 'Cm_Cache_Backend_Redis') {
                    unset($config['cache']['frontend'][$cacheName]);
                }
            }
        }

        return $config;
    }

    /**
     * Send Password Reset Email for the admin user.
     * We need to do this for environments that don't have the ADMIN_PASSWORD variable set so that the admin has
     * a way to log in.
     */
    private function sendPasswordResetEmail()
    {
        if (!$this->isInstalling || empty($this->env) || empty($this->adminEmail) || !empty($this->env->getVariables()["ADMIN_PASSWORD"])) {
            return;
        }
        /* TODO: Instead of calling our own command to do it, we will wait until a reset command gets added to Magento core
         * // $this->env->log("Sending password reset email to admin user \"{$this->adminUsername}\" at $this->adminEmail");
         * // $this->env->execute("vendor/bin/m2-ece-send-password-reset-email");
         * Note: For now, we will just email them the admin URL where they can manually click "Forgot your password" to get a password reset email.
         */
        $adminurl = $this->urls['secure'][''] . $this->adminUrl;
        $this->env->log("Emailing admin URL to admin user \"{$this->adminUsername}\" at $this->adminEmail");
        mail(
            $this->adminEmail,
            "Magento Commerce Cloud - Admin URL",
            "Welcome to Magento Commerce (Cloud)!\n"
                . "To properly log into your provisioned Magento installation Admin panel, you need to change your Admin password. To update your password, click this link to access the Admin Panel: {$adminurl} . When the page opens, click the \"Forgot your password\" link. You should receive a password update email at {$this->adminEmail} . Just in case, check your spam box if you don't see the email immediately.\n"
                . "After the password is updated, you can login with the username {$this->adminUsername} and the new password.\n"
                . "Need help? Please see http://devdocs.magento.com/guides/v2.2/cloud/onboarding/onboarding-tasks.html\n"
                . "Thank you,\n"
                . "Magento Commerce (Cloud)\n",
            "From: Magento Cloud <accounts@magento.cloud>"
        );
    }
}
