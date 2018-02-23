<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Util\ArrayManager;
use Magento\MagentoCloud\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * @inheritdoc
 */
class CommandDumpTest extends AbstractTest
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->arrayManager = new ArrayManager();
    }

    public function testDump()
    {
        $application = $this->bootstrap->createApplication(['variables' => ['ADMIN_EMAIL' => 'admin@example.com']]);

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);

        $config = $this->readConfig($application);
        $this->assertSame(1, count($config));
        $this->assertArrayHasKey('modules', $config);

        /** Test that config:dump works correctly if it is run more than one time */
        $this->checkConfigDumpCommand($application);
        $this->checkConfigDumpCommand($application);
    }

    /**
     * @param Application $application
     */
    private function checkConfigDumpCommand(Application $application)
    {
        $this->executeAndAssert(ConfigDump::NAME, $application);
        $config = $this->readConfig($application);
        $this->assertSame(4, count($config));
        $flattenKeysConfig = implode(array_keys($this->arrayManager->flatten($config, '#')));

        $this->assertContains('#modules', $flattenKeysConfig);
        $this->assertContains('#scopes', $flattenKeysConfig);
        $this->assertContains('#system/default/general/locale/code', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/static/sign', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/front_end_development_workflow', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/template', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/js', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/css', $flattenKeysConfig);
        $this->assertContains('#system/stores', $flattenKeysConfig);
        $this->assertContains('#system/websites', $flattenKeysConfig);
        $this->assertContains('#admin_user/locale/code', $flattenKeysConfig);
    }

    /**
     * @param string $commandName
     * @param Application $application
     * @return void
     */
    private function executeAndAssert(string $commandName, Application $application)
    {
        $application->getContainer()->set(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\App\Logger::class
        );
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param Application $application
     * @return array
     */
    private function readConfig(Application $application): array
    {
        /** @var FileList $fileList */
        $fileList = $application->getContainer()->get(FileList::class);
        /** @var File $file */
        $file = $application->getContainer()->get(File::class);

        $configPath = $fileList->getConfig();

        if ($file->isExists($configPath)) {
            return require $configPath;
        }

        return [];
    }
}
