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
     * Flag to determine which service to add: 'activemq', 'rabbitmq', or 'none'
     *
     * @var string
     */
    protected string $serviceToAdd = 'activemq';

    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        // Reset to default service for each test
        $this->serviceToAdd = 'activemq';
        parent::_before($I);
    }

    /**
     * Get configuration from deployed environment
     *
     * @param  \CliTester $I
     * @return array
     */
    protected function getConfig(\CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        // Use 'fpm' container instead of 'deploy' because deploy container exits after completion
        // and fpm has access to the same /app directory via shared volumes
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, 'fpm'));
        return include $destination;
    }

    /**
     * Test default ActiveMQ configuration
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // Check that queue configuration is present and correctly set
        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');
        $I->assertArrayHasKey('stomp', $config['queue'], 'STOMP configuration missing from queue config');

        $this->checkArraySubset(
            [
                'host' => $data['expectedHost'],
                'port' => $data['expectedPort'],
                'user' => $data['expectedUser'],
                'password' => $data['expectedPassword'],
            ],
            $config['queue']['stomp'],
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
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider customConfigurationDataProvider
     */
    public function testCustomConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

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
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider wrongConfigurationDataProvider
     */
    public function testWrongConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

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
     * Test ActiveMQ fallback to RabbitMQ
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider fallbackToRabbitMqDataProvider
     */
    public function testFallbackToRabbitMq(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // Should have queue configuration
        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');

        // Check for either AMQP (RabbitMQ) or STOMP (ActiveMQ Artemis)
        $queueType = isset($config['queue']['amqp']) ? 'amqp' : 'stomp';
        $I->assertArrayHasKey(
            $queueType,
            $config['queue'],
            'Queue configuration (AMQP or STOMP) missing from queue config'
        );

        $this->checkArraySubset(
            $data['expectedRabbitMqConfig'],
            $config['queue'][$queueType],
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
     * Test queue configuration without any message broker (uses DB)
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider noMessageBrokerDataProvider
     */
    public function testNoMessageBroker(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        // When no message broker is available, queue config should exist with only consumers_wait_for_messages
        // No AMQP (RabbitMQ) or STOMP (ActiveMQ) should be present - database queue is used
        $I->assertArrayHasKey('queue', $config, 'Queue configuration should be present');
        $I->assertArrayNotHasKey('amqp', $config['queue'], 'AMQP configuration should not be present (no RabbitMQ)');
        $I->assertArrayNotHasKey('stomp', $config['queue'], 'STOMP configuration should not be present (no ActiveMQ)');

        // Should only have consumers_wait_for_messages setting
        $I->assertArrayHasKey(
            'consumers_wait_for_messages',
            $config['queue'],
            'consumers_wait_for_messages should be present'
        );
        $I->assertEquals(
            0,
            $config['queue']['consumers_wait_for_messages'],
            'consumers_wait_for_messages should be 0'
        );

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

    /**
     * Override prepareWorkplace to add ActiveMQ or RabbitMQ service based on test scenario
     *
     * @param \CliTester $I
     * @param string     $templateVersion
     * @return void
     */
    protected function prepareWorkplace(\CliTester $I, string $templateVersion): void
    {
        parent::prepareWorkplace($I, $templateVersion);

        // Add the appropriate service based on the test scenario
        if ($this->serviceToAdd === 'activemq') {
            $this->addActiveMqService($I);
        } elseif ($this->serviceToAdd === 'rabbitmq') {
            $this->addRabbitMqService($I);
        }
        // If serviceToAdd is null or empty, no message broker service is added
    }

    /**
     * Add ActiveMQ Artemis service to services.yaml and .magento.app.yaml
     *
     * @param \CliTester $I
     * @return void
     */
    protected function addActiveMqService(\CliTester $I): void
    {
        // Read current services.yaml
        $services = $I->readServicesYaml();

        // Add ActiveMQ Artemis service if not present
        if (!isset($services['activemq-artemis'])) {
            $services['activemq-artemis'] = [
                'type' => 'activemq-artemis:2.42.0',
                'disk' => 1024,
            ];
            $I->writeServicesYaml($services);
        }

        // Read current .magento.app.yaml
        $app = $I->readAppMagentoYaml();

        // Add ActiveMQ Artemis relationship if not present
        if (!isset($app['relationships']['activemq-artemis'])) {
            $app['relationships']['activemq-artemis'] = 'activemq-artemis:activemq-artemis';
            $I->writeAppMagentoYaml($app);
        }
    }

    /**
     * Add RabbitMQ service to services.yaml and .magento.app.yaml
     *
     * @param \CliTester $I
     * @return void
     */
    protected function addRabbitMqService(\CliTester $I): void
    {
        // Read current services.yaml
        $services = $I->readServicesYaml();

        // Add RabbitMQ service if not present
        if (!isset($services['rabbitmq'])) {
            $services['rabbitmq'] = [
                'type' => 'rabbitmq:4.1',
                'disk' => 1024,
            ];
            $I->writeServicesYaml($services);
        }

        // Read current .magento.app.yaml
        $app = $I->readAppMagentoYaml();

        // Add RabbitMQ relationship if not present
        if (!isset($app['relationships']['rabbitmq'])) {
            $app['relationships']['rabbitmq'] = 'rabbitmq:rabbitmq';
            $I->writeAppMagentoYaml($app);
        }
    }
}
