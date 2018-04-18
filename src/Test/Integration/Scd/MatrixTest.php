<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Scd;

use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command;
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
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->application = Bootstrap::getInstance()->createApplication();
        $this->urlManager = $this->application->getContainer()->get(UrlManager::class);
        $this->clientFactory = $this->application->getContainer()->get(ClientFactory::class);
    }

    /**
     * @param string $commandName
     * @return void
     */
    private function executeAndAssert(string $commandName)
    {
        $commandTester = new CommandTester(
            $this->application->get($commandName)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param array $environment
     */
    public function testScdOnDeploy()
    {
        $this->executeAndAssert(Command\Build::NAME);
        $this->executeAndAssert(Command\Deploy::NAME);
        $this->executeAndAssert(Command\Prestart::NAME);
        $this->executeAndAssert(Command\PostDeploy::NAME);

        $this->assertContentPresence();
    }

    private function assertContentPresence()
    {
        $client = $this->clientFactory->create();
        $response = $client->request('GET', $this->urlManager->getBaseUrl());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(
            'Home Page',
            $response->getBody()->getContents(),
            'Check "Home Page" phrase presence'
        );
        $this->assertContains(
            'CMS homepage content goes here.',
            $response->getBody()->getContents(),
            'Check "CMS homepage content goes here." phrase presence'
        );
    }
}
