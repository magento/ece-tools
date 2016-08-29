<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Environment;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{
    const MAGIC_ROUTE = '{default}';

    const PREFIX_SECURE = 'https://';
    const PREFIX_UNSECURE = 'http://';

    const GIT_MASTER_BRANCH = 'master';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    protected $urls = ['unsecure' => [], 'secure' => []];

    protected $defaultCurrency = 'USD';

    protected $dbHost;
    protected $dbName;
    protected $dbUser;
    protected $dbPassword;

    protected $adminUsername;
    protected $adminFirstname;
    protected $adminLastname;
    protected $adminEmail;
    protected $adminPassword;
    protected $adminUrl;

    protected $redisHost;
    protected $redisPort;
    protected $redisSessionDb = '0';
    protected $redisCacheDb = '1'; // Value hard-coded in pre-deploy.php

    protected $solrHost;
    protected $solrPath;
    protected $solrPort;
    protected $solrScheme;

    protected $isMasterBranch = null;
    protected $desiredApplicationMode;
    protected $isRecompileDI = false;
    protected $staticFilesCleaningStrategy = false;

    /**
     * @var Environment
     */
    private $env;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento-cloud:deploy')
            ->setDescription('Deploy an instance of Magento on the Magento Cloud');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->env = new Environment();
        $this->deploy();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     */
    private function deploy()
    {
        $this->env->log("Start deploy.");
        $this->saveEnvironmentData();

        if (!$this->isInstalled()) {
            $this->installMagento();
        } else {
            $this->updateMagento();
        }
        $this->processMagentoMode();
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

        $this->staticFilesCleaningStrategy = isset($var["CLEAN_STATIC_FILES"]) && $var["CLEAN_STATIC_FILES"] == 'enabled' ? true : false;
        $this->isRecompileDI = isset($var["RECOMPILE_DI"]) && $var["RECOMPILE_DI"] == 'enabled' ? true : false;

        $this->desiredApplicationMode = isset($var["APPLICATION_MODE"]) ? $var["APPLICATION_MODE"] : false;
        $this->desiredApplicationMode =
            in_array($this->desiredApplicationMode, array(self::MAGENTO_DEVELOPER_MODE, self::MAGENTO_PRODUCTION_MODE))
                ? $this->desiredApplicationMode
                : false;

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
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * @return bool
     */
    public function isInstalled()
    {
        $configFile = 'app/etc/env.php';
        $installed = false;
        if (file_exists($configFile)) { //TODO
            $data = include $configFile;
            if (isset($data['install']) && isset($data['install']['date'])) {
                $this->env->log("Magento was installed on " . $data['install']['date']);
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
        $this->env->log("File env.php does not contain installation date. Installing Magento.");

        $urlUnsecure = $this->urls['unsecure'][''];
        $urlSecure = $this->urls['secure'][''];

        $command =
            "cd bin/; /usr/bin/php ./magento setup:install \
            --session-save=db \
            --cleanup-database \
            --currency=$this->defaultCurrency \
            --base-url=$urlUnsecure \
            --base-url-secure=$urlSecure \
            --language=en_US \
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

        $this->env->execute($command);
        $this->updateConfig();
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

        $this->executeDbQuery("update admin_user set firstname = '$this->adminFirstname', lastname = '$this->adminLastname', email = '$this->adminEmail', username = '$this->adminUsername', password='{$this->generatePassword($this->adminPassword)}' where user_id = '1';");
    }

    /**
     * Update SOLR configuration
     */
    private function updateSolrConfiguration()
    {
        $this->env->log("Updating SOLR configuration.");

        if ($this->solrHost !== null && $this->solrPort !== null && $this->solrPath !== null && $this->solrHost !== null) {
            $this->executeDbQuery("update core_config_data set value = '$this->solrHost' where path = 'catalog/search/solr_server_hostname' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrPort' where path = 'catalog/search/solr_server_port' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrScheme' where path = 'catalog/search/solr_server_username' and scope_id = '0';");
            $this->executeDbQuery("update core_config_data set value = '$this->solrPath' where path = 'catalog/search/solr_server_path' and scope_id = '0';");
        }
    }

    /**
     * Update secure and unsecure URLs
     */
    private function updateUrls()
    {
        $this->env->log("Updating secure and unsecure URLs.");

        foreach ($this->urls as $urlType => $urls) {
            foreach ($urls as $route => $url) {
                $prefix = 'unsecure' === $urlType ? self::PREFIX_UNSECURE : self::PREFIX_SECURE;
                if (!strlen($route)) {
                    $this->executeDbQuery("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and scope_id = '0';");
                    continue;
                }
                $likeKey = $prefix . $route . '%';
                $likeKeyParsed = $prefix . str_replace('.', '---', $route) . '%';
                $this->executeDbQuery("update core_config_data set value = '$url' where path = 'web/$urlType/base_url' and (value like '$likeKey' or value like '$likeKeyParsed');");
            }
        }
    }


    /**
     * Run Magento setup upgrade
     */
    private function setupUpgrade()
    {
        $this->env->log("Running setup upgrade.");

        $this->env->execute(
            "cd bin/; /usr/bin/php ./magento setup:upgrade --keep-generated"
        );
    }

    /**
     * Clear Magento file based cache
     */
    private function clearCache()
    {
        $this->env->log("Clearing application cache.");

        $this->env->execute(
            "cd bin/; /usr/bin/php ./magento cache:flush"
        );
    }

    /**
     * Update env.php file content
     */
    private function updateConfiguration()
    {
        $this->env->log("Updating env.php database configuration.");

        $configFileName = "app/etc/env.php";

        $config = include $configFileName;

        $config['db']['connection']['default']['username'] = $this->dbUser;
        $config['db']['connection']['default']['host'] = $this->dbHost;
        $config['db']['connection']['default']['dbname'] = $this->dbName;
        $config['db']['connection']['default']['password'] = $this->dbPassword;

        $config['db']['connection']['indexer']['username'] = $this->dbUser;
        $config['db']['connection']['indexer']['host'] = $this->dbHost;
        $config['db']['connection']['indexer']['dbname'] = $this->dbName;
        $config['db']['connection']['indexer']['password'] = $this->dbPassword;

        if ($this->redisHost !== null && $this->redisPort !== null) {
            $this->env->log("Updating env.php Redis cache configuration.");
            $config['cache'] = [
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

            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $this->redisHost,
                    'port' => $this->redisPort,
                    'database' => $this->redisSessionDb
                ]
            ];
        }
        $config['backend']['frontName'] = $this->adminUrl;

        $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($config, true) . ';';

        file_put_contents($configFileName, $updatedConfig);
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
            if (isset($_ENV["MAGENTO_CLOUD_ENVIRONMENT"]) && $_ENV["MAGENTO_CLOUD_ENVIRONMENT"] == self::GIT_MASTER_BRANCH) {
                $this->isMasterBranch = true;
            } else {
                $this->isMasterBranch = false;
            }
        }
        return $this->isMasterBranch;
    }

    /**
     * Executes database query
     *
     * $query must completed, finished with semicolon (;)
     * If branch isn't master - disable Google Analytics
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
     */
    private function executeDbQuery($query)
    {
        $password = strlen($this->dbPassword) ? sprintf('-p%s', $this->dbPassword) : '';
        return $this->env->execute("mysql -u $this->dbUser -h $this->dbHost -e \"$query\" $password $this->dbName");
    }


    /**
     * Based on variable APPLICATION_MODE. Production mode by default
     */
    private function processMagentoMode()
    {
        $desiredApplicationMode = ($this->desiredApplicationMode) ? $this->desiredApplicationMode : self::MAGENTO_PRODUCTION_MODE;
        $this->env->log("Set Magento application to '$desiredApplicationMode' mode");

        /* Enable application mode */
        if ($desiredApplicationMode == self::MAGENTO_PRODUCTION_MODE) {
            $this->generateStaticFiles();
            $this->env->log("Enable production mode");
            $configFileName = "app/etc/env.php";
            $config = include $configFileName;
            $config['MAGE_MODE'] = 'production';
            $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($config, true) . ';';
            file_put_contents($configFileName, $updatedConfig);
        } else if ($desiredApplicationMode == self::MAGENTO_DEVELOPER_MODE) {
            $this->env->log("Enable developer mode");
            $this->env->execute("cd bin/; /usr/bin/php ./magento deploy:mode:set $desiredApplicationMode");
        }
    }

    private function generateStaticFiles()
    {
        $this->env->log("Enabling Maintenance mode.");
        /* Enable maintenance mode */
        $this->env->execute("cd bin/; /usr/bin/php ./magento maintenance:enable");

        /* If static content is not cleaned, it will be incrementally updated */
        if ($this->staticFilesCleaningStrategy) {
            $this->env->log("Removing existing static content.");
            $this->env->execute('rm -rf var/view_preprocessed/*');
            $this->env->execute('rm -rf pub/static/*');
        }

        /* Generate static assets */
        $this->env->log("Extract locales");
        $locales = [];

        $output = $this->executeDbQuery("select value from core_config_data where path='general/locale/code' and scope = 'default';");
        $default = "en_US";
        if(is_array($output) && count($output) == 2){
            $default = $output[1];
            $default = preg_replace('/[^A-Za-z_]/', "", $default); //No sql injection
        }

        $output = $this->executeDbQuery("select distinct value from core_config_data where path='general/locale/code' and scope <> 'default' and value <> '$default';");
        if (is_array($output) && count($output) > 1) {
            $locales = $output;
            array_shift($locales);
        }

        $logMessage = $locales ? "Generating static content for locale: $default" : "Generating static content.";
        $this->env->log($logMessage);

        $this->env->execute("cd bin/; /usr/bin/php -d zend.enable_gc=0 ./magento setup:static-content:deploy $default");

        if(count($locales) > 0){
            $logMessage = "Running remaining locals in parallel";
            $this->env->log($logMessage);
            $parallelCommands = "";
            foreach ($locales as $locale){
                $parallelCommands .= "/usr/bin/php ./bin/magento setup:static-content:deploy $locale" . '\n';
            }
            $threads = isset($_ENV["MAGENTO_STATIC_CONTENT_THREADS"]) ? $_ENV["MAGENTO_STATIC_CONTENT_THREADS"]: 1;
            $this->env->execute("printf '$parallelCommands' | xargs -I CMD -P" . (int)$threads . " bash -c CMD");
        }

        /* Disable maintenance mode */
        $this->env->execute("cd bin/; /usr/bin/php ./magento maintenance:disable");
        $this->env->log("Maintenance mode is disabled.");
    }
    /**
     * Parse MagentoCloud routes to more readable format.
     */
    private function initRoutes()
    {
        $this->env->log("Initializing routes.");

        $routes = $this->env->getRoutes();

        foreach($routes as $key => $val) {
            if ($val["type"] !== "upstream") {
                continue;
            }

            $urlParts = parse_url($val['original_url']);
            $originalUrl = str_replace(self::MAGIC_ROUTE, '', $urlParts['host']);

            if(strpos($key, self::PREFIX_UNSECURE) === 0) {
                $this->urls['unsecure'][$originalUrl] = $key;
                continue;
            }

            if(strpos($key, self::PREFIX_SECURE) === 0) {
                $this->urls['secure'][$originalUrl] = $key;
                continue;
            }
        }

        if (!count($this->urls['secure'])) {
            $this->urls['secure'] = $this->urls['unsecure'];
        }

        $this->env->log(sprintf("Routes: %s", var_export($this->urls, true)));
    }

}
