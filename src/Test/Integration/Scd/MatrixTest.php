<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Scd;

use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Test\Integration\AbstractTest;
use Magento\MagentoCloud\Test\Integration\Bootstrap;
use Magento\MagentoCloud\Util\UrlManager;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class MatrixTest extends AbstractTest
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->application = Bootstrap::getInstance()->createApplication();
        $this->urlManager = $this->application->getContainer()->get(UrlManager::class);
        $this->clientFactory = $this->application->getContainer()->get(ClientFactory::class);
        $this->file = $this->application->getContainer()->get(File::class);
        $this->systemList = $this->application->getContainer()->get(SystemList::class);
    }

    /**
     * @param string $commandName
     * @return void
     */
    private function executeAndAssertCodeOK(string $commandName)
    {
        $commandTester = new CommandTester(
            $this->application->get($commandName)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param string $file
     * @dataProvider scdOnDeployDataProvider
     */
    public function testScdOnDeploy(string $file)
    {
        $this->file->copy(
            $file,
            $this->systemList->getMagentoRoot() . '/.magento.env.yaml'
        );

        $this->executeAndAssertCodeOK(Command\Build::NAME);
        $this->executeAndAssertCodeOK(Command\Deploy::NAME);
        $this->executeAndAssertCodeOK(Command\PostDeploy::NAME);

        $this->assertContentPresence();
    }

    /**
     * @return array
     */
    public function scdOnDeployDataProvider(): array
    {
        return [
            [
                __DIR__ . '/_files/env_matrix_1.yaml',
            ],
            [
                __DIR__ . '/_files/env_matrix_2.yaml',
            ],
        ];
    }

    private function assertContentPresence()
    {
        $client = $this->clientFactory->create();
        $response = $client->request('GET', $this->urlManager->getBaseUrl());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(
            'Home Page',
            (string)$response->getBody(),
            'Check "Home Page" phrase presence'
        );
        $this->assertContains(
            'CMS homepage content goes here.',
            (string)$response->getBody(),
            'Check "CMS homepage content goes here." phrase presence'
        );
    }
}
