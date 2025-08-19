<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Functional\Acceptance
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Checks ActiveMQ queue configuration
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Functional\Acceptance
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
abstract class ActiveMqCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        //Do nothing...
    }

    /**
     * Get configuration from deployed environment
     *
     * @param \CliTester $I
     * @return array
     */
    private function getConfig(\CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        return require $destination;
    }

    /**
     * Test default ActiveMQ configuration
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // Check that queue configuration is present and correctly set
        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');
        $I->assertArrayHasKey('amqp', $config['queue'], 'AMQP configuration missing from queue config');

        $this->checkArraySubset(
            [
                'host' => $data['expectedHost'],
                'port' => $data['expectedPort'],
                'user' => $data['expectedUser'],
                'password' => $data['expectedPassword'],
                'virtualhost' => $data['expectedVirtualHost'],
            ],
            $config['queue']['amqp'],
            $I
        );

        // Check consumers wait for messages configuration for Magento >= 2.2
        if (isset($data['expectedConsumersWait'])) {
            $I->assertArrayHasKey(
                'consumers_wait_for_messages',
                $config['queue'],
                'Consumers wait for messages configuration missing'
            );
            $I->assertSame(
                $data['expectedConsumersWait'],
                $config['queue']['consumers_wait_for_messages'],
                'Wrong consumers wait for messages configuration'
            );
        }

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Data provider for default configuration test
     *
     * @return array
     */
    abstract protected function defaultConfigurationDataProvider(): array;

    /**
     * Test ActiveMQ configuration with custom settings
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider customConfigurationDataProvider
     */
    public function testCustomConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');
        $this->checkArraySubset(
            $data['expectedQueueConfig'],
            $config['queue'],
            $I
        );

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Data provider for custom configuration test
     *
     * @return array
     */
    abstract protected function customConfigurationDataProvider(): array;

    /**
     * Test ActiveMQ wrong configuration
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider wrongConfigurationDataProvider
     */
    public function testWrongConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['wrongConfiguration']);

        $I->assertSame($data['buildSuccess'], $I->runDockerComposeCommand('run build cloud-build'));
        if (isset($data['errorBuildMessage'])) {
            $I->seeInOutput($data['errorBuildMessage']);
        }
        
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        
        $I->assertSame($data['deploySuccess'], $I->runDockerComposeCommand('run deploy cloud-deploy'));
        if (isset($data['errorDeployMessage'])) {
            $I->seeInOutput($data['errorDeployMessage']);
        }
    }

    /**
     * Data provider for wrong configuration test
     *
     * @return array
     */
    abstract protected function wrongConfigurationDataProvider(): array;

    /**
     * Test ActiveMQ connection failure
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider connectionFailureDataProvider
     */
    public function testConnectionFailure(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        
        // Deployment should fail due to connection issues
        $I->assertFalse(
            $I->runDockerComposeCommand('run deploy cloud-deploy'),
            'Deploy phase should have failed due to connection issues'
        );
    }

    /**
     * Data provider for connection failure test
     *
     * @return array
     */
    abstract protected function connectionFailureDataProvider(): array;

    /**
     * Test ActiveMQ fallback to RabbitMQ
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider fallbackToRabbitMqDataProvider
     */
    public function testFallbackToRabbitMq(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // Should have queue configuration from RabbitMQ fallback
        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');
        $I->assertArrayHasKey('amqp', $config['queue'], 'AMQP configuration missing from queue config');

        $this->checkArraySubset(
            $data['expectedRabbitMqConfig'],
            $config['queue']['amqp'],
            $I
        );

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Data provider for RabbitMQ fallback test
     *
     * @return array
     */
    abstract protected function fallbackToRabbitMqDataProvider(): array;

    /**
     * Test queue configuration without any message broker
     *
     * @param \CliTester           $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider noMessageBrokerDataProvider
     */
    public function testNoMessageBroker(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // Should not have queue configuration when no message broker is available
        $I->assertArrayNotHasKey('queue', $config, 'Queue configuration should not be present');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Data provider for no message broker test
     *
     * @return array
     */
    abstract protected function noMessageBrokerDataProvider(): array;
}
