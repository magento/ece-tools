<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Illuminate\Config\Repository;

/**
 * Class Config.
 */
class Config extends Repository
{
    const DEFAULT_CONTAINER = 'build';
    const CONTAINER_DEPLOY = 'deploy';

    public function __construct()
    {
        parent::__construct([
            'repo' => [
                'url' => 'https://github.com/magento/magento-cloud',
                'branch' => 'master'
            ],
            'system' => [
                'magento_dir' => $_ENV['MAGENTO_ROOT'] ?? '/var/www/magento',
                'ece_dir' => $_ENV['ECE_ROOT'] ?? '/var/www/ece-tools'
            ],
            'env' => [
                'url' => [
                    'base' => 'http://localhost:8030',
                    'secure_base' => 'http://localhost:8030'
                ]
            ]
        ]);
    }
}
