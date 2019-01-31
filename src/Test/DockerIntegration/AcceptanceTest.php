<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AcceptanceTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        $processFactory = new ProcessFactory();
        $callback = function ($type, $buffer) {
            echo $buffer;
        };

        $processFactory->create('docker-compose up -d')
            ->setTimeout(null)
            ->mustRun($callback);

        parent::setUpBeforeClass();
    }

    protected function setUp()
    {
        $processFactory = new ProcessFactory();
        $callback = function ($type, $buffer) {
            echo $buffer;
        };

        $processFactory->create(sprintf(
            'docker-compose run cli bash -c "git clone %s -b %s %s"',
            'https://github.com/magento/magento-cloud',
            'master',
            $_ENV['MAGENTO_ROOT'] ?? '/var/www/magento'
        ))->setTimeout(null)
            ->mustRun($callback);
        $processFactory->create('docker-compose run cli bash -c "composer install -d /var/www/magento" --no-dev')
            ->setTimeout(null)
            ->mustRun($callback);

        parent::setUp();
    }

    protected function tearDown()
    {
        $processFactory = new ProcessFactory();
        $callback = function ($type, $buffer) {
            echo $buffer;
        };

        $processFactory->create('docker-compose run cli bash -c "rm -rf /var/www/magento/*"')
            ->mustRun($callback);

        parent::tearDown();
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        $processFactory = new ProcessFactory();
        $callback = function ($type, $buffer) {
            echo $buffer;
        };

        $processFactory->create('docker-compose down -v')
            ->setTimeout(null)
            ->mustRun($callback);

        parent::tearDownAfterClass();
    }

    public function testDefault()
    {
        $processFactory = new ProcessFactory();
        $callback = function ($type, $buffer) {
            echo $buffer;
        };

        $code = $processFactory->createCompose('/var/www/ece-tools/bin/ece-tools build', 'cli')
            ->setTimeout(null)
            ->run($callback);

        $this->assertSame(0, $code);

        $code = $processFactory->createCompose('/var/www/ece-tools/bin/ece-tools deploy', 'cli')
            ->setTimeout(null)
            ->run($callback);

        $this->assertSame(0, $code);

        $code = $processFactory->createCompose('/var/www/ece-tools/bin/ece-tools post-deploy', 'cli')
            ->setTimeout(null)
            ->run($callback);

        $this->assertSame(0, $code);

        $process = $processFactory->create('curl http://localhost:8080');
        $process->run();

        $this->assertSame(0, $process->getExitCode());
        $this->assertContains('Home page', $process->getOutput());
    }
}
