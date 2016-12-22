<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Environment;

/**
 * CLI command for dumping SCD related config.
 */
class SCDConfigDump extends Command
{
    /**
     * Option to skip regeneration of config
     */
    const KEEP_CONFIG = 'keep-config';

    private $requiredConfigs = [
        'scopes',
        'system/default/general/locale',
        'dev/static/sign',
        'dev/static/front_end_development_workflow',
        'dev/static/template',
        'dev/static/js',
        'dev/static/css',
        'system/stores',
        'system/websites',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::KEEP_CONFIG,
                null,
                InputOption::VALUE_NONE,
                'Prevents existing config being overwritten. ' . PHP_EOL
            )
        ];

        $this->setName('magento-cloud:configdump')
            ->setDescription('Dump config related to SCD')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keepConfig = $input->getOption(self::KEEP_CONFIG);
        $returnCode = 0;
        if (!$keepConfig) {
            $command = $this->getApplication()->find('app:config:dump');
            $returnCode = $command->run($input, $output);
        }
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.local.php';

        if ($returnCode == 0 && file_exists($configFile)) {
            $oldConfig = include $configFile;
            $oldConfig = $this->flatten($oldConfig);
            var_dump($oldConfig);
            $newConfig = [];
            foreach ($oldConfig as $scopeCode => $scopeConfig) {
                foreach ($this->requiredConfigs as $requiredConfig) {
                    if (0 === strpos($scopeCode, $requiredConfig)) {
                        echo PHP_EOL . "save " . $scopeCode;
                        $newConfig[$scopeCode] = $scopeConfig;
                    }
                }
            }
        }
    }

    private function flatten($array, $prefix ='')
    {
        $result = [];
        foreach($array as $key=>$value) {
            if(is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '/');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
}