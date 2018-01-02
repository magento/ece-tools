<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\IntegrationDocker;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class AcceptanceTest extends TestCase
{
    protected function setUp()
    {
        shell_exec(sprintf(
            'cd %s && docker-compose run cli magento-installer',
            ECE_BP . '/tests/integration-docker'
        ));
        shell_exec(sprintf(
            'cd %s && docker-compose up -d',
            ECE_BP . '/tests/integration-docker'
        ));
    }

    protected function tearDown()
    {
        shell_exec(sprintf(
            'cd %s && docker-compose down',
            ECE_BP . '/tests/integration-docker'
        ));
    }

    public function testDefault()
    {
        $environment = new Repository(
            require ECE_BP . '/tests/integration-docker/environment.php.dist'
        );

        $_ENV = array_replace($_ENV, [
            'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                $environment->get('variables', [])
            )),
            'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(
                $environment->get('relationships', [])
            )),
            'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(
                $environment->get('routes', [])
            )),
        ]);


        $container = new Container(new DirectoryList(
            ECE_BP,
            ECE_BP . '/tests/integration-docker/magento'
        ));
        $container->set(ShellInterface::class, Shell::class);
        $container->set(LoggerInterface::class, Logger::class);
        $application = new Application($container);

//        $commandTester = new CommandTester($application->get(Build::NAME));
//        $commandTester->execute([]);
//        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester($application->get(Deploy::NAME));
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
