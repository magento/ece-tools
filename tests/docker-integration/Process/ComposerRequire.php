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
class ComposerRequire extends Bash
{
    public function __construct(string $version)
    {
        $config = new Config();

        parent::__construct(sprintf(
            'composer require magento/magento-cloud-metapackage %s -d %s --no-interaction --no-update && composer update',
            $version,
            $config->get('system.magento_dir')
        ), Config::DEFAULT_CONTAINER);
    }
}
