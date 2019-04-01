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
class Ece extends Bash
{
    /**
     * @param string $command
     * @param string $container
     * @param array $variables
     */
    public function __construct(string $command, string $container, array $variables = [])
    {
        $config = new Config();

        parent::__construct(
            sprintf('%s/bin/ece-tools ', $config->get('system.ece_dir')) . $command,
            $container,
            $variables
        );
    }
}
