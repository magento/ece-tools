<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Utils\PasswordGenerator;

/**
 * @inheritdoc
 */
class InstallUpdate implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    const MAGIC_ROUTE = '{default}';

    const PREFIX_SECURE = 'https://';
    const PREFIX_UNSECURE = 'http://';

    private $urls = ['unsecure' => [], 'secure' => []];

    private $amqpHost;
    private $amqpPort;
    private $amqpUser;
    private $amqpPasswd;
    private $amqpVirtualhost = '/';
    private $amqpSsl = '';

    private $redisHost;
    private $redisPort;
    private $redisSessionDb = '0';
    private $redisCacheDb = '1'; // Value hard-coded in pre-deploy.php

    private $solrHost;
    private $solrPath;
    private $solrPort;
    private $solrScheme;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DeployConfig $deployConfig
     * @param Environment $environment
     * @param Adapter $adapter
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        DeployConfig $deployConfig,
        Environment $environment,
        Adapter $adapter,
        PasswordGenerator $passwordGenerator
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->deployConfig = $deployConfig;
        $this->environment = $environment;
        $this->adapter = $adapter;
        $this->passwordGenerator = $passwordGenerator;
    }

    public function execute()
    {
        $this->loadEnvironmentData();

        if (!$this->deployConfig->isInstalled()) {
            $this->install();
        } else {
            $this->update();
        }
    }

    private function loadEnvironmentData()
    {
        $this->logger->info('Preparing environment specific data.');

        $this->initRoutes();

        $relationships = $this->environment->getRelationships();

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
    }

    /**
     * Parse MagentoCloud routes to more readable format.
     */
    private function initRoutes()
    {
        $this->logger->info('Initializing routes.');

        $routes = $this->environment->getRoutes();

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

        $this->logger->info(sprintf("Routes: %s", var_export($this->urls, true)));
    }

    private function install()
    {
        $this->logger->info('Installing Magento.');

        $urlUnsecure = $this->urls['unsecure'][''];
        $urlSecure = $this->urls['secure'][''];

        $command =
            "php ./bin/magento setup:install \
            --session-save=db \
            --cleanup-database \
            --currency={$this->environment->getDefaultCurrency()} \
            --base-url=$urlUnsecure \
            --base-url-secure=$urlSecure \
            --language={$this->environment->getAdminLocale()} \
            --timezone=America/Los_Angeles \
            --db-host={$this->environment->getDbHost()} \
            --db-name={$this->environment->getDbName()} \
            --db-user={$this->environment->getDbUser()} \
            --backend-frontname={$this->environment->getAdminUrl()} \
            --admin-user={$this->environment->getAdminUsername()} \
            --admin-firstname={$this->environment->getAdminFirstname()} \
            --admin-lastname={$this->environment->getAdminLastname()} \
            --admin-email={$this->environment->getAdminEmail()} \
            --admin-password={$this->environment->getAdminPassword()}";

        if (strlen($this->environment->getDbPassword())) {
            $command .= " \
            --db-password={$this->environment->getDbPassword()}";
        }

        $command .= $this->environment->getVerbosityLevel();

        $this->shell->execute($command);

        $this->setSecureAdmin();
        $this->updateConfig();
    }

    /**
     * Update Magento configuration
     */
    private function update()
    {
        $this->logger->info('File env.php contains installation date. Updating configuration.');

        $this->updateConfig();
        $this->setupUpgrade();
        $this->clearCache();
    }

    /**
     * Update secure admin
     */
    public function setSecureAdmin()
    {
        $this->logger->info('Setting secure admin');

        $command =
            "php ./bin/magento config:set web/secure/use_in_adminhtml 1";
        $command .= $this->environment->getVerbosityLevel();
        $this->shell->execute($command);
    }

    private function updateConfig()
    {
        $this->logger->info('Updating configuration from environment variables.');
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
        $this->logger->info('Updating admin credentials.');

        $password = $this->passwordGenerator->generate(
            $this->environment->getAdminPassword()
        );

        // @codingStandardsIgnoreStart
        $this->executeDbQuery(
            "update admin_user set firstname = '{$this->environment->getAdminFirstname()}', lastname = '{$this->environment->getAdminLastname()}', email = '{$this->environment->getAdminEmail()}', username = '{$this->environment->getAdminUsername()}', password='$password' where user_id = '1';"
        );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Update SOLR configuration
     */
    private function updateSolrConfiguration()
    {
        $this->logger->info('Updating SOLR configuration.');

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
        if ($this->environment->isUpdateUrlsEnabled()) {
            $this->logger->info('Updating secure and unsecure URLs.');

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
            $this->logger->info('Skipping URL updates');
        }
    }

    /**
     * Run Magento setup upgrade
     */
    private function setupUpgrade()
    {
        $this->logger->info('Saving disabled modules.');

        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            unlink(Environment::REGENERATE_FLAG);
        }

        try {
            /* Enable maintenance mode */
            $this->logger->info('Enabling Maintenance mode.');
            $this->shell->execute("php ./bin/magento maintenance:enable {$this->environment->getVerbosityLevel()}");

            $this->logger->info('Running setup upgrade.');
            $this->shell->execute(
                "php ./bin/magento setup:upgrade --keep-generated -n {$this->environment->getVerbosityLevel()}"
            );

            /* Disable maintenance mode */
            $this->shell->execute(
                "php ./bin/magento maintenance:disable {$this->environment->getVerbosityLevel()}"
            );
            $this->logger->info('Maintenance mode is disabled.');
        } catch (\RuntimeException $e) {
            //Rollback required by database
            throw new \RuntimeException($e->getMessage(), 6);
        }
        if (file_exists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            unlink(Environment::REGENERATE_FLAG);
        }
    }

    /**
     * Clear Magento file based cache
     */
    private function clearCache()
    {
        $this->logger->info('Clearing application cache.');

        $this->shell->execute(
            "php ./bin/magento cache:flush {$this->environment->getVerbosityLevel()}"
        );
    }

    /**
     * Update env.php file content
     */
    private function updateConfiguration()
    {
        $this->logger->info('Updating env.php database configuration.');

        $configFileName = $this->getConfigFilePath();

        $config = include $configFileName;

        $config['db']['connection']['default']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['default']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['default']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['default']['password'] = $this->environment->getDbPassword();

        $config['db']['connection']['indexer']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['indexer']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['indexer']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['indexer']['password'] = $this->environment->getDbPassword();

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
            $this->logger->info('Updating env.php Redis cache configuration.');
            $config['cache'] = $this->getRedisCacheConfiguration();
            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $this->redisHost,
                    'port' => $this->redisPort,
                    'database' => $this->redisSessionDb,
                ],
            ];
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $config['backend']['frontName'] = $this->environment->getAdminUrl();
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
        $this->logger->info('Removing AMQP configuration from env.php.');

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
     * Clears configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisConfiguration($config)
    {
        $this->logger->info('Removing redis cache and session configuration from env.php.');

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


    private function getRedisCacheConfiguration()
    {
        return [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => $this->redisHost,
                        'port' => $this->redisPort,
                        'database' => $this->redisCacheDb,
                    ],
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => $this->redisHost,
                        'port' => $this->redisPort,
                        'database' => $this->redisCacheDb,
                    ],
                ],
            ],
        ];
    }

    /**
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    private function getConfigFilePath()
    {
        return MAGENTO_ROOT . 'app/etc/env.php';
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
        return $this->adapter->execute($query);
    }
}
