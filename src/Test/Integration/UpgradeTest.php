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
        $this->markTestIncomplete();

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

        $assertBuild = function () use ($application) {
            $commandTester = new CommandTester($application->get(Build::NAME));
            $commandTester->execute([]);
            $this->assertSame(0, $commandTester->getStatusCode());
        };
        $assertDeploy = function () use ($application) {
            $commandTester = new CommandTester($application->get(Deploy::NAME));
            $commandTester->execute([]);
            $this->assertSame(0, $commandTester->getStatusCode());
        };

        $assertBuild();
        $assertDeploy();
        $this->assertContentPresence();

        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s -n -d %s',
            $to,
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf('composer update -n --no-dev -d %s', $sandboxDir));

        $assertBuild();
        $assertDeploy();
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

        $routes = array_keys($routes);
        $defaultRoute = reset($routes);
        $pageContent = file_get_contents($defaultRoute);

        $this->assertContains(
            'Home Page',
            $pageContent,
            'Check "Home Page" phrase presence'
        );
        $this->assertContains(
            'CMS homepage content goes here.',
            $pageContent,
            'Check "CMS homepage content goes here." phrase presence'
        );
    }
}
