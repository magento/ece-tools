<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\PostDeploy;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class UpgradeTest extends AbstractTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.2.0');
    }

    /**
     * @param string $fromVersion
     * @param string $toVersion
     * @dataProvider defaultDataProvider
     */
    public function testDefault(string $fromVersion, string $toVersion)
    {
        $this->bootstrap->execute(sprintf(
            'rm -rf %s/vendor/*',
            $this->bootstrap->getSandboxDir()
        ));

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
        $executeAndAssert(PostDeploy::NAME);

        $this->assertContentPresence();
        $this->updateToVersion($toVersion);

        $executeAndAssert(Build::NAME);
        $executeAndAssert(Deploy::NAME);
        $executeAndAssert(PostDeploy::NAME);

        $this->assertContentPresence();
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['2.2.0', '2.2.*'],
        ];
    }

    private function assertContentPresence()
    {
        $routes = $this->bootstrap->getEnv('routes', []);
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
            'rm -rf %s/vendor/*',
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf(
            'composer require magento/product-enterprise-edition %s --no-update -n -d %s',
            $version,
            $sandboxDir
        ));
        $this->bootstrap->execute(sprintf('composer update -n -d %s', $sandboxDir));
    }
}
