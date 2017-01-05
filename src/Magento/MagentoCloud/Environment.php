<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const MAGENTO_ROOT = __DIR__ . '/../../../../../../';
    const STATIC_CONTENT_DEPLOY_FLAG = 'var/static_content_deploy';
    const PRE_DEPLOY_FLAG = self::MAGENTO_ROOT . 'var/.predeploy_in_progress';

    public $writableDirs = ['var/di', 'var/generation', 'app/etc', 'pub/media'];

    /**
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRoutes()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_ROUTES"]), true);
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_RELATIONSHIPS"]), true);
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getVariables()
    {
        return json_decode(base64_decode($_ENV["MAGENTO_CLOUD_VARIABLES"]), true);
    }


    public function log($message)
    {
        echo sprintf('[%s] %s', date("Y-m-d H:i:s"), $message) . PHP_EOL;
    }

    public function execute($command)
    {
        $this->log('Command:'.$command);

        exec(
            $command,
            $output,
            $status
        );

        $this->log('Status:'.var_export($status, true));
        $this->log('Output:'.var_export($output, true));

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }

    public function backgroundExecute($command)
    {
        $command = "nohup {$command} 1>/dev/null 2>&1 &";
        $this->log("Execute command in background: $command");
        shell_exec($command);
    }

    public function setStaticDeployInBuild($flag)		
    {
        if ($flag) {
         $this->log('Setting flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
         touch(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        } else {
            if ($this->isStaticDeployInBuild()) {
                    $this->log('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                    unlink(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                }
        }
    }

    public function isStaticDeployInBuild()
    {
        return file_exists(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }
}
