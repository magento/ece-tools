<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\Prestart;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class UpgradeTest extends AbstractTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();
        $this->bootstrap->destroy();
    }

    /**
     * @param string $fromVersion
     * @param string $toVersion
     * @dataProvider defaultDataProvider
     */
    public function testDefault(string $fromVersion, string $toVersion)
    {
        $this->bootstrap->run($fromVersion);

        $application = $this->bootstrap->createApplication([]);

        $executeAndAssert = function ($commandName) use ($application) {
            $application->getContainer()->set(
                \Psr\Log\LoggerInterface::class,
                \Magento\MagentoCloud\App\Logger::class
            );
            $commandTester = new CommandTester($application->get($commandName));
            $commandTester->execute([]);
            $this->assertSame(0, $commandTester->getStatusCode());
        };

        $executeAndAssert(Build::NAME);
        $executeAndAssert(Deploy::NAME);
        $executeAndAssert(Prestart::NAME);

        $this->assertContentPresence();
        $this->updateToVersion($toVersion);

        $executeAndAssert(Build::NAME);
        $executeAndAssert(Deploy::NAME);
        $executeAndAssert(Prestart::NAME);
        $this->assertContentPresence();
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['2.1.*', '2.2.0'],
            ['2.2.0', '2.2.*'],
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

    /**
     * @param string $version
     */
    private function updateToVersion($version)
    {
        $sandboxDir = $this->bootstrap->getSandboxDir();
        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s --no-update -n -d %s --ignore-platform-reqs',
            $version,
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf('composer update -n --no-dev -d %s  --ignore-platform-reqs', $sandboxDir));
    }
}
