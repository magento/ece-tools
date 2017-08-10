<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for dumping SCD related config.
 */
class SCDConfigDump extends Command
{
    private $requiredConfigKeys = [
        'modules',
        'scopes',
        'system/default/general/locale/code',
        'system/default/dev/static/sign',
        'system/default/dev/front_end_development_workflow',
        'system/default/dev/template',
        'system/default/dev/js',
        'system/default/dev/css',
        'system/default/advanced/modules_disable_output',
        'system/stores',
        'system/websites',
    ];

    /**
     * @var Environment
     */
    private $env;

    public function __construct()
    {
        $this->env = new Environment();

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('dump')
            ->setDescription('Dump static content');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
            $this->env->execute("php bin/magento app:config:dump");

            if (file_exists($configFile)) {
                $oldConfig = include $configFile;
                $newConfig = [];

                foreach ($this->requiredConfigKeys as $requiredConfigKey) {
                    $oldConfigCopy = $oldConfig;
                    $configKeys = explode('/', $requiredConfigKey);

                    //get value of the config recursively
                    foreach ($configKeys as $configKey) {
                        if (isset($oldConfigCopy[$configKey])) {
                            $oldConfigCopy = $oldConfigCopy[$configKey];
                        } else {
                            $oldConfigCopy = null;
                        }
                    }
                    //set value in new array.
                    if (isset($oldConfigCopy)) {
                        $newConfig = $this->buildNestedArray($configKeys, $oldConfigCopy, $newConfig);
                    }
                }

                //only saving general/locale/code
                $configLocales = array_keys($newConfig['system']['stores']);
                foreach ($configLocales as $configLocale) {
                    if (isset($newConfig['system']['stores'][$configLocale]['general']['locale']['code'])) {
                        $temp = $newConfig['system']['stores'][$configLocale]['general']['locale']['code'];
                        unset($newConfig['system']['stores'][$configLocale]);
                        $newConfig['system']['stores'][$configLocale]['general']['locale']['code'] = $temp;
                    }
                }
                //unsetting base_url
                if (isset($newConfig['system']['stores']['admin']['web']['secure']['base_url'])) {
                    unset($newConfig['system']['stores']['admin']['web']['secure']['base_url']);
                }
                if (isset($newConfig['system']['stores']['admin']['web']['unsecure']['base_url'])) {
                    unset($newConfig['system']['stores']['admin']['web']['unsecure']['base_url']);
                }
                //locales for admin user
                $newConfig['admin_user']['locale']['code'] =
                    $this->executeDbQuery('SELECT DISTINCT interface_locale FROM admin_user');

                $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($newConfig, true) . ";\n";
                file_put_contents($configFile, $updatedConfig);
                $this->env->execute('php bin/magento app:config:import -n');
            } else {
                $this->env->log('No config file');
            }
        } catch (\RuntimeException $e) {
            $this->env->log('Something went wrong in running app:config:dump');
            $this->env->log($e->getTraceAsString());
        }
    }

    private function buildNestedArray($keys, $val, $out)
    {
        $data = &$out;
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            $data = &$data[$key];
        }
        $data = $val;
        return $out;
    }

    /**
     * Executes database query
     *
     * @param string $query
     * $query must be completed, finished with semicolon (;)
     * @return array
     */
    private function executeDbQuery($query)
    {
        $relationships = $this->env->getRelationships();
        $dbHost = $relationships["database"][0]["host"];
        $dbName = $relationships["database"][0]["path"];
        $dbUser = $relationships["database"][0]["username"];
        $dbPassword = $relationships["database"][0]["password"];
        $password = strlen($dbPassword) ? sprintf('-p%s', $dbPassword) : '';
        return $this->env->execute("mysql --skip-column-names -u $dbUser -h $dbHost -e \"$query\" $password $dbName");
    }
}
