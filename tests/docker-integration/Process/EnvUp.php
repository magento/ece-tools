<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

/**
 * @inheritdoc
 */
class EnvUp extends Process
{
    public function __construct()
    {
        parent::__construct('docker-compose down -v && docker-compose down -v');
    }
}
