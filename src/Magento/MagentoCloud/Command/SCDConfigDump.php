<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;

/**
 * CLI command for dumping SCD related config.
 */
class SCDConfigDump
{
    private $requiredConfigKeys = [
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

    public function execute()
    {
        $returnCode = 0;
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.local.php';

        if ($returnCode == 0 && file_exists($configFile)) {
            $oldConfig = include $configFile;
            $newConfig = [];

            foreach ($this->requiredConfigKeys as $requiredConfigKey) {
                $oldConfigCopy = $oldConfig;
                $configKeys = explode('/', $requiredConfigKey);

                //get value of the config recursively
                foreach( $configKeys as $configKey) {
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
            $updatedConfig = '<?php'  . "\n" . 'return ' . var_export($newConfig, true) . ";\n";
            file_put_contents($configFile, $updatedConfig);
        }
    }

    private function buildNestedArray($keys, $val, $out) {
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
}
