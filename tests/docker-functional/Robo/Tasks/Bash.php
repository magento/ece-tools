<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\DockerFunctional\Robo\Tasks;

use Magento\MagentoCloud\Test\DockerFunctional\Robo\Tasks\DockerCompose;

class Bash extends DockerCompose\Run
{
    protected $runWrapper = 'bash -c "%s"';
}
