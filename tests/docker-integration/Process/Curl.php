<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

use Magento\MagentoCloud\Test\DockerIntegration\Config;

class Curl extends Process
{
    public function __construct(string $page = '')
    {
        $config = new Config();

        parent::__construct(sprintf(
            'curl %s/%s | grep Home',
            $config->get('env.url.base'),
            $page
        ));
    }
}
