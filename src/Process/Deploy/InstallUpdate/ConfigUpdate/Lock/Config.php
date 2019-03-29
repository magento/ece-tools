<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock;

use Magento\MagentoCloud\Config\Environment;

/**
 * Returns lock configuration.
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns lock configuration.
     *
     * If there is MAGENTO_CLOUD_LOCKS_DIR the file lock provider will be used,
     * otherwise the db lock provider will be used.
     *
     * @return array
     */
    public function get(): array
    {
        $lockPath = $this->environment->getEnv('MAGENTO_CLOUD_LOCKS_DIR');
        if ($lockPath) {
            return [
                'provider' => 'file',
                'config' => [
                    'path' => $lockPath
                ],
            ];
        }

        return [
            'provider' => 'db',
            'config' => [
                'prefix' => null,
            ],
        ];
    }
}
