<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Process\ProcessInterface;

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

    private $solrHost;
    private $solrPath;
    private $solrPort;
    private $solrScheme;

    private $isMasterBranch = null;
    private $magentoApplicationMode;
    private $cleanStaticViewFiles;
    private $staticDeployThreads;
    private $staticDeployExcludeThemes = [];
    private $adminLocale;
    private $doDeployStaticContent;

    private $verbosityLevel;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var ProcessInterface
     */
    private $process;

    public function __construct(ProcessInterface $process)
    {
        $this->process = $process;
        $this->env = new Environment();

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
        $this->process->execute();

        $this->env->log("Starting deploy.");
        $this->saveEnvironmentData();
        if (!$this->isInstalled()) {
            $this->installMagento();
        } else {
            $this->updateMagento();
        }
        $this->staticContentDeploy();
        $this->disableGoogleAnalytics();
        $this->env->log("Deployment complete.");
    }

    /**
     * Parse and save information about environment configuration and variables.
     */
    private function saveEnvironmentData()
    {
        $this->env->log("Preparing environment specific data.");

        $this->initRoutes();

        $relationships = $this->env->getRelationships();
        $var = $this->env->getVariables();

        $this->dbHost = $relationships["database"][0]["host"];
        $this->dbName = $relationships["database"][0]["path"];
        $this->dbUser = $relationships["database"][0]["username"];
        $this->dbPassword = $relationships["database"][0]["password"];

        $this->adminUsername = isset($var["ADMIN_USERNAME"]) ? $var["ADMIN_USERNAME"] : "admin";
        $this->adminFirstname = isset($var["ADMIN_FIRSTNAME"]) ? $var["ADMIN_FIRSTNAME"] : "John";
        $this->adminLastname = isset($var["ADMIN_LASTNAME"]) ? $var["ADMIN_LASTNAME"] : "Doe";
        $this->adminEmail = isset($var["ADMIN_EMAIL"]) ? $var["ADMIN_EMAIL"] : "john@example.com";
        $this->adminPassword = isset($var["ADMIN_PASSWORD"]) ? $var["ADMIN_PASSWORD"] : "admin12";
        $this->adminUrl = isset($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : "admin";
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

        if (isset($relationships["solr"]) && count($relationships['solr']) > 0) {
            $this->solrHost = $relationships["solr"][0]["host"];
            $this->solrPath = $relationships["solr"][0]["path"];
            $this->solrPort = $relationships["solr"][0]["port"];
            $this->solrScheme = $relationships["solr"][0]["scheme"];
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
     * Verifies is Magento installed based on install date in env.php
     *
     * @return bool
     */
    public function isInstalled()
    {
        $configFile = $this->getConfigFilePath();
        $installed = false;

        //1. from environment variables check if db exists and has tables
        //2. check if core_config_data and setup_module tables exist
        //3. check install date

        $this->env->log('Checking if db exists and has tables');
        $output = $this->executeDbQuery('SHOW TABLES');
        if (is_array($output) && count($output) > 1) {
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
            "php ./bin/magento setup:install \
            --session-save=db \
            --cleanup-database \
            --currency=$this->defaultCurrency \
            --base-url=$urlUnsecure \
            --base-url-secure=$urlSecure \
            --language=$this->adminLocale \
            --timezone=America/Los_Angeles \
            --db-host=$this->dbHost \
            --db-name=$this->dbName \
            --db-user=$this->dbUser \
            --backend-frontname=$this->adminUrl \
            --admin-user=$this->adminUsername \
            --admin-firstname=$this->adminFirstname \
            --admin-lastname=$this->adminLastname \
            --admin-email=$this->adminEmail \
            --admin-password=$this->adminPassword";

        if (strlen($this->dbPassword)) {
            $command .= " \
            --db-password=$this->dbPassword";
        }

        $command .= $this->verbosityLevel;

        $this->env->execute($command);

        $this->setSecureAdmin();
        $this->updateConfig();
    }

    /**
     * Update secure admin
     */
    public function setSecureAdmin()
    {
        $this->env->log("Setting secure admin");
        $command =
            "php ./bin/magento config:set web/secure/use_in_adminhtml 1";
        $command .= $this->verbosityLevel;
        $this->env->execute($command);
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
        $this->updateSolrConfiguration();
        $this->updateUrls();
    }

    /**
     * Update admin credentials
     */
    private function updateAdminCredentials()
    {
        $this->env->log("Updating admin credentials.");

        // @codingStandardsIgnoreStart
        $this->executeDbQuery("update admin_user set firstname = '$this->adminFirstname', lastname = '$this->adminLastname', email = '$this->adminEmail', username = '$this->adminUsername', password='{$this->generatePassword($this->adminPassword)}' where user_id = '1';");
        // @codingStandardsIgnoreEnd
    }

    /**
     * Update SOLR configuration
     */
    private function updateSolrConfiguration()
    {
        $this->env->log("Updating SOLR configuration.");

        if ($this->solrHost !== null
            && $this->solrPort !== null
            && $this->solrPath !== null
            && $this->solrHost !== null
        ) {
            // @codingStandardsIgnoreStart
            $this->executeDbQuery("update core_config_data set value = '$this->solrHost' where path = 'catalog/search/solr_server_hostname' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrPort' where path = 'catalog/search/solr_server_port' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrScheme' where path = 'catalog/search/solr_server_username' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrPath' where path = 'catalog/search/solr_server_path' and scope_id = '0';");
            // @codingStandardsIgnoreEnd
        }
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
                        $this->executeDbQuery("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and scope_id = '0';");
                        // @codingStandardsIgnoreEnd
                        continue;
                    }
                    $likeKey = $prefix . $route . '%';
                    $likeKeyParsed = $prefix . str_replace('.', '---', $route) . '%';
                    // @codingStandardsIgnoreStart
                    $this->executeDbQuery("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and (value like '$likeKey' or value like '$likeKeyParsed');");
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
        $this->env->log("Saving disabled modules.");

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

        $configFileName = $this->getConfigFilePath();

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
            $config['cache'] = $this->getRedisCacheConfiguration();
            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $this->redisHost,
                    'port' => $this->redisPort,
                    'database' => $this->redisSessionDb
                ]
            ];
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $config['backend']['frontName'] = $this->adminUrl;

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
     * Generates admin password using default Magento settings
     */
    private function generatePassword($password)
    {
        $saltLenght = 32;
        $charsLowers = 'abcdefghijklmnopqrstuvwxyz';
        $charsUppers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsDigits = '0123456789';
        $randomStr = '';
        $chars = $charsLowers . $charsUppers . $charsDigits;

        // use openssl lib
        for ($i = 0, $lc = strlen($chars) - 1; $i < $saltLenght; $i++) {
            $bytes = openssl_random_pseudo_bytes(PHP_INT_SIZE);
            $hex = bin2hex($bytes); // hex() doubles the length of the string
            $rand = abs(hexdec($hex) % $lc); // random integer from 0 to $lc
            $randomStr .= $chars[$rand]; // random character in $chars
        }
        $salt = $randomStr;
        $version = 1;
        $hash = hash('sha256', $salt . $password);

        return implode(
            ':',
            [
                $hash,
                $salt,
                $version
            ]
        );
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
            $this->executeDbQuery("update core_config_data set value = 0 where path = 'google/analytics/active';");
        }
    }

    /**
     * Executes database query
     *
     * @param string $query
     * $query must be completed, finished with semicolon (;)
     * @return mixed
     */
    private function executeDbQuery($query)
    {
        $password = strlen($this->dbPassword) ? sprintf('-p%s', $this->dbPassword) : '';

        return $this->env->execute("mysql -u $this->dbUser -h $this->dbHost -e \"$query\" $password $this->dbName");
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

        // @codingStandardsIgnoreStart
        $this->env->execute(
            "php ./bin/magento setup:static-content:deploy  -f $jobsOption $excludeThemesOptions $locales {$this->verbosityLevel}"
        );
        // @codingStandardsIgnoreEnd

        /* Disable maintenance mode */
        $this->env->execute("php ./bin/magento maintenance:disable {$this->verbosityLevel}");
        $this->env->log("Maintenance mode is disabled.");
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
        $output = $this->executeDbQuery($query);

        if (is_array($output) && count($output) > 1) {
            //first element should be shifted as it is the name of column
            array_shift($output);
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
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    private function getConfigFilePath()
    {
        return Environment::MAGENTO_ROOT . 'app/etc/env.php';
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
}
