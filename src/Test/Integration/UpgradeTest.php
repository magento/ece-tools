<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class UpgradeTest extends TestCase
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento module:enable --all',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @param string $from
     * @param string $to
     * @dataProvider defaultDataProvider
     */
    public function testDefault(string $from, string $to)
    {
        $application = $this->bootstrap->createApplication([]);
        $sandboxDir = $this->bootstrap->getSandboxDir();
        $config = $this->bootstrap->mergeConfig([]);

        if ($config->get('deploy.type') !== Bootstrap::DEPLOY_TYPE_PROJECT) {
            $this->markTestIncomplete('Git upgrades does not supported.');
        }

        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s -n -d %s',
            $from,
            $sandboxDir
        ));

        $assert = function ($commandName) use ($application) {
            $commandTester = new CommandTester($application->get($commandName));
            $commandTester->execute([]);
            $this->assertSame(0, $commandTester->getStatusCode());
        };

        $assert(Build::NAME);
        $assert(Deploy::NAME);
        $this->assertContentPresence();

        $this->bootstrap->execute('composer clear-cache');
        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s --no-update -n -d %s',
            $to,
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf('composer update -n --no-dev -d %s', $sandboxDir));

        $assert(Build::NAME);
        $assert(Deploy::NAME);
        $this->assertContentPresence();
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['^2.1', '2.2.0'],
            ['2.2.0', '^2.2'],
        ];
    }

    private function assertContentPresence()
    {
        $config = $this->bootstrap->mergeConfig([]);
        $routes = $config->get('routes');

        if ($config->get('skip_front_check') === true || !$routes) {
            return;
        }

        $defaultRoute = array_keys($routes)[0];
        $pageContent = file_get_contents($defaultRoute);

        $this->assertContains('Home Page', $pageContent, 'Check "Home Page" phrase presence');
    }
}
