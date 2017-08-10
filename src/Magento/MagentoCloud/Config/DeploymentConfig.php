<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class DeploymentConfig
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        ShellInterface $shell,
        Adapter $adapter
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->adapter = $adapter;
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * @return bool
     */
    public function isInstalled()
    {
        $configFile = $this->getConfigFilePath();
        $isInstalled = false;

        //1. from environment variables check if db exists and has tables
        //2. check if core_config_data and setup_module tables exist
        //3. check install date

        $this->logger->info('Checking if db exists and has tables');
        $output = $this->adapter->execute('SHOW TABLES');
        if (is_array($output) && count($output) > 1) {
            if (!in_array('core_config_data', $output) || !in_array('setup_module', $output)) {
                $this->logger->info('Missing either core_config_data or setup_module table');
                exit(5);
            } elseif (file_exists($configFile)) {
                $data = include $configFile;
                if (isset($data['install']) && isset($data['install']['date'])) {
                    $this->logger->info("Magento was installed on " . $data['install']['date']);
                    $isInstalled = true;
                } else {
                    $config['install']['date'] = date('r');
                    $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
                    file_put_contents($configFile, $updatedConfig);
                    $isInstalled = true;
                }
            } else {
                $this->shell->execute('touch ' . $configFile);
                $config['install']['date'] = date('r');
                $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
                file_put_contents($configFile, $updatedConfig);
                $isInstalled = true;
            }
        }

        return $isInstalled;
    }

    /**
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    public function getConfigFilePath()
    {
        return Environment::MAGENTO_ROOT . 'app/etc/env.php';
    }
}
