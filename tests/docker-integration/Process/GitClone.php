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
class GitClone extends Bash
{
    public function __construct(string $version)
    {
        $config = new Config();
        $url = $config->get('repo.url');

        parent::__construct(sprintf(
            'git init && git remote add origin %s && git fetch && git checkout %s',
            $url,
            $version
        ), Config::DEFAULT_CONTAINER);
    }
}
