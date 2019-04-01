<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

use Magento\MagentoCloud\Test\DockerIntegration\Config;

class Copy extends Process
{
    /**
     * @param string $file
     * @param string $to
     */
    public function __construct(string $file, string $to)
    {
        $config = new Config();
        $dir = pathinfo($to, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            mkdir(pathinfo($to, PATHINFO_DIRNAME), 0755, true);
        }

        parent::__construct(
            sprintf(
                'docker cp %s:%s %s',
                Config::CONTAINER_DEPLOY,
                $config->get('system.magento_dir') . $file,
                $to
            )
        );
    }
}
