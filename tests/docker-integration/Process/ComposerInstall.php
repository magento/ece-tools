<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

use Magento\MagentoCloud\Test\DockerIntegration\Config;

/**
 * @inheritdoc
 */
class ComposerInstall extends Bash
{
    public function __construct()
    {
        $config = new Config();

        parent::__construct(sprintf(
            'composer install -d %s --no-dev --no-interaction',
            $config->get('system.magento_dir')
        ), Config::DEFAULT_CONTAINER);
    }
}
