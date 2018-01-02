<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\IntegrationDocker;

use Magento\MagentoCloud\Shell\ShellInterface;

class Shell implements ShellInterface
{
    public function execute(string $command)
    {
        $testsDir = __DIR__ . '/../../../tests/integration-docker';

        echo shell_exec(sprintf(
            "cd %s && docker-compose run cli bash -c 'cd %s && %s'",
            $testsDir,
            '/var/www/magento',
            $command
        ));
    }
}
