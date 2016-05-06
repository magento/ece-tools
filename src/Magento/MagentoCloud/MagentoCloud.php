<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud;

class MagentoCloud
{
    const MAGIC_ROUTE = '{default}';

    const PREFIX_SECURE = 'https://';
    const PREFIX_UNSECURE = 'http://';

    const GIT_MASTER_BRANCH = 'master';

    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    protected $debugMode = true;

    protected $magentoReadWriteDirs = ['var/di', 'var/generation', 'app/etc', 'pub/media'];

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
    protected $redisScheme;
    protected $redisPort;

    protected $solrHost;
    protected $solrPath;
    protected $solrPort;
    protected $solrScheme;

    protected $isMasterBranch = null;
    protected $desiredApplicationMode;
    protected $isRecompileDI = false;
    protected $staticFilesCleaningStrategy = false;

    /**
     * Parse MagentoCloud routes to more readable format.
     */
    public function initRoutes()
    {
        $this->log("Initializing routes.");

        $routes = $this->getRoutes();

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

        $this->log(sprintf("Routes: %s", var_export($this->urls, true)));
    }

    /**
     * Build application: clear temp directory and move writable directories content to temp.
     */
    public function build()
    {
        $this->log("Start build.");

        $this->applyPatches();

        $this->compileDI();
        $this->clearTemp();
        $this->log("Copying read/write directories to temp directory.");
        $this->execute('rm -rf app/etc/env.php');
        $this->execute('rm -rf app/etc/config.php');

        foreach ($this->magentoReadWriteDirs as $dir) {
            $this->execute(sprintf('mkdir -p ./init/%s', $dir));
            $this->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R %s/* ./init/%s/"', $dir, $dir));
            $this->execute(sprintf('rm -rf %s', $dir));
            $this->execute(sprintf('mkdir %s', $dir));
        }
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     */
    public function deploy()
    {
        $this->log("Start deploy.");

        $this->_init();

        $this->log("Copying read/write directories back.");

        foreach ($this->magentoReadWriteDirs as $dir) {
            $this->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
            $this->log(sprintf('Copied directory: %s', $dir));
        }

        if (!$this->isInstalled()) {
            $this->installMagento();
        } else {
            $this->updateMagento();
        }
        $this->processMagentoMode();
        $this->disableGoogleAnalytics();
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * @return bool
     */
    private function isInstalled()
    {
        $configFile = 'app/etc/env.php';
        $installed = false;
        if (file_exists($configFile)) {
            $data = include $configFile;
            if (isset($data['install']) && isset($data['install']['date'])) {
                $this->log("Magento was installed on " . $data['install']['date']);
                $installed = true;
            }
        }
        return $installed;
    }

    /**
     * Prepare data needed to install Magento
     */
    protected function _init()
    {
        $this->log("Preparing environment specific data.");

        $this->initRoutes();

        $relationships = $this->getRelationships();
        $var = $this->getVariables();

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
            $this->redisScheme = $relationships['redis'][0]['scheme'];
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
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    protected function getRoutes()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_ROUTES"]), true);
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    protected function getRelationships()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_RELATIONSHIPS"]), true);
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    protected function getVariables()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_VARIABLES"]), true);
    }

    /**
     * Run Magento installation
     */
    protected function installMagento()
    {
        $this->log("File env.php does not contain installation date. Installing Magento.");

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

        $this->execute($command);
        $this->updateConfig();
    }

    /**
     * Update Magento configuration
     */
    protected function updateMagento()
    {
        $this->log("File env.php contains installation date. Updating configuration.");
        $this->setupUpgrade();
        $this->updateConfig();
        $this->clearCache();
    }

    protected function updateConfig()
    {
        $this->log("Updating configuration from environment variables.");
        $this->updateConfiguration();
        $this->updateAdminCredentials();
        $this->updateSolrConfiguration();
        $this->updateUrls();
    }

    /**
     * Update admin credentials
     */
    protected function updateAdminCredentials()
    {
        $this->log("Updating admin credentials.");

        $this->executeDbQuery("update admin_user set firstname = '$this->adminFirstname', lastname = '$this->adminLastname', email = '$this->adminEmail', username = '$this->adminUsername', password='{$this->generatePassword($this->adminPassword)}' where user_id = '1';");
    }

    /**
     * Update SOLR configuration
     */
    protected function updateSolrConfiguration()
    {
        $this->log("Updating SOLR configuration.");

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
    protected function updateUrls()
    {
        $this->log("Updating secure and unsecure URLs.");

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
     * Clear content of temp directory
     */
    protected function clearTemp()
    {
        $this->log("Clearing temporary directory.");

        $this->execute('rm -rf ../init/*');
    }

    /**
     * Run Magento setup upgrade
     */
    protected function setupUpgrade()
    {
        $this->log("Running setup upgrade.");

        $this->execute(
            "cd bin/; /usr/bin/php ./magento setup:upgrade --keep-generated"
        );
    }

    /**
     * Clear Magento file based cache
     */
    protected function clearCache()
    {
        $this->log("Clearing application cache.");

        $this->execute(
            "cd bin/; /usr/bin/php ./magento cache:flush"
        );
    }

    /**
     * Update env.php file content
     */
    protected function updateConfiguration()
    {
        $this->log("Updating env.php database configuration.");

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
            $this->log("Updating env.php Redis cache configuration.");
            $config['cache'] = [
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => $this->redisHost,
                            'port' => $this->redisPort
                        ]
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => $this->redisHost,
                            'port' => $this->redisPort
                        ]
                    ]
                ]
		    ];

            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $this->redisHost,
                    'port' => $this->redisPort,
                ]
            ];
        }
        $config['backend']['frontName'] = $this->adminUrl;

        $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($config, true) . ';';

        file_put_contents($configFileName, $updatedConfig);
    }

    protected function log($message)
    {
        echo sprintf('[%s] %s', date("Y-m-d H:i:s"), $message) . PHP_EOL;
    }

    protected function execute($command)
    {
        if ($this->debugMode) {
            $this->log('Command:'.$command);
        }

        exec(
            $command,
            $output,
            $status
        );

        if ($this->debugMode) {
            $this->log('Status:'.var_export($status, true));
            $this->log('Output:'.var_export($output, true));
        }

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }


    /**
     * Generates admin password using default Magento settings
     */
    protected function generatePassword($password)
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
    protected function isMasterBranch()
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
    protected function disableGoogleAnalytics()
    {
        if (!$this->isMasterBranch()) {
            $this->log("Disabling Google Analytics");
            $this->executeDbQuery("update core_config_data set value = 0 where path = 'google/analytics/active';");
        }
    }

    /**
     * Executes database query
     *
     * @param string $query
     * $query must be completed, finished with semicolon (;)
     */
    protected function executeDbQuery($query)
    {
        $password = strlen($this->dbPassword) ? sprintf('-p%s', $this->dbPassword) : '';
        return $this->execute("mysql -u $this->dbUser -h $this->dbHost -e \"$query\" $password $this->dbName");
    }

    protected function compileDI()
    {
        $this->log("Run DI compilation");
        $this->execute('rm -rf var/generation/*');
        $this->execute('rm -rf var/di/*');
        $this->execute("cd bin/; /usr/bin/php ./magento module:enable --all");
        $this->execute("cd bin/; /usr/bin/php ./magento setup:di:compile");
    }

    /**
     * Based on variable APPLICATION_MODE. Production mode by default
     */
    protected function processMagentoMode()
    {
        $desiredApplicationMode = ($this->desiredApplicationMode) ? $this->desiredApplicationMode : self::MAGENTO_PRODUCTION_MODE;
        $this->log("Set Magento application to '$desiredApplicationMode' mode");

        /* Enable application mode */
        if ($desiredApplicationMode == self::MAGENTO_PRODUCTION_MODE) {
            $this->generateStaticFiles();
            $this->log("Enable production mode");
            $configFileName = "app/etc/env.php";
            $config = include $configFileName;
            $config['MAGE_MODE'] = 'production';
            $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($config, true) . ';';
            file_put_contents($configFileName, $updatedConfig);
        } else if ($desiredApplicationMode == self::MAGENTO_DEVELOPER_MODE) {
            $this->log("Enable developer mode");
            $this->execute("cd bin/; /usr/bin/php ./magento deploy:mode:set $desiredApplicationMode");
        }
    }

    protected function generateStaticFiles()
    {
        $this->log("Enabling Maintenance mode.");
        /* Enable maintenance mode */
        $this->execute("cd bin/; /usr/bin/php ./magento maintenance:enable");

        /* If static content is not cleaned, it will be incrementally updated */
        if ($this->staticFilesCleaningStrategy) {
            $this->log("Removing existing static content.");
            $this->execute('rm -rf var/view_preprocessed/*');
            $this->execute('rm -rf pub/static/*');
        }

        /* Generate static assets */
        $this->log("Extract locales");
        $locales = '';
        $output = $this->executeDbQuery("select value from core_config_data where path='general/locale/code';");
        if (is_array($output) && count($output) > 1) {
            $locales = $output;
            array_shift($locales);
            $locales = implode(' ', $locales);
        }
        $logMessage = $locales ? "Generating static content for locales $locales." : "Generating static content.";
        $this->log($logMessage);
        $this->execute("cd bin/; /usr/bin/php ./magento setup:static-content:deploy $locales");
        /* Disable maintenance mode */
        $this->execute("cd bin/; /usr/bin/php ./magento maintenance:disable");
        $this->log("Maintenance mode is disabled.");
    }

    /**
     * Apply any existing patches
     */
    protected function applyPatches()
    {
        $this->log("Patching Magento.");
        $this->execute('/usr/bin/php ' . __DIR__ . '/../../../patch.php');
    }
}
