<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\Prestart;
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
     * @param string $fromVersion
     * @param string $toVersion
     * @dataProvider defaultDataProvider
     */
    public function testDefault(string $fromVersion, string $toVersion)
    {
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

        $this->updateToVersion($fromVersion);

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

    /**
     * @param string $version
     */
    private function updateToVersion($version)
    {
        $sandboxDir = $this->bootstrap->getSandboxDir();
        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s --no-update -n -d %s',
            $version,
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf('composer update -n --no-dev -d %s', $sandboxDir));
    }
}
