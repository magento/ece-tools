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


}
