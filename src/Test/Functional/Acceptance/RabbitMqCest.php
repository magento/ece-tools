<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks RabbitMQ queue configuration
 */
abstract class RabbitMqCest extends AbstractCest
{
    /**
     * Flag to determine which service to add: 'rabbitmq' or 'none'
     *
     * @var string
     */
    protected string $serviceToAdd = 'rabbitmq';

    /**
     * RabbitMQ version used when rabbitmq service is added.
     *
     * @var string
     */
    protected string $rabbitMqVersion = '4.2';

    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        $this->serviceToAdd = 'rabbitmq';
        $this->rabbitMqVersion = '4.2';
        parent::_before($I);
    }

    /**
     * Get configuration from deployed environment
     *
     * @param \CliTester $I
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
     * Test default RabbitMQ configuration
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        if (isset($data['rabbitMqVersion'])) {
            $this->rabbitMqVersion = $data['rabbitMqVersion'];
        }

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

        $I->assertArrayHasKey('queue', $config, 'Queue configuration missing from env.php');
        $I->assertArrayHasKey('amqp', $config['queue'], 'AMQP configuration missing from queue config');

        $this->checkArraySubset(
            [
                'host' => $data['expectedHost'],
                'port' => $data['expectedPort'],
                'user' => $data['expectedUser'],
                'password' => $data['expectedPassword'],
            ],
            $config['queue']['amqp'],
            $I
        );

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
     * Test RabbitMQ configuration with custom settings
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider customConfigurationDataProvider
     */
    public function testCustomConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        if (isset($data['rabbitMqVersion'])) {
            $this->rabbitMqVersion = $data['rabbitMqVersion'];
        }

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
        $this->configureRabbitMqUserForInstall($I, $data['configuration']);
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
     * Creates/updates custom RabbitMQ users before cloud-deploy installation step.
     *
     * Magento validates AMQP credentials during setup:install, so custom test credentials
     * must exist in the RabbitMQ service ahead of deploy.
     *
     * @param \CliTester $I
     * @param array $configuration
     */
    protected function configureRabbitMqUserForInstall(\CliTester $I, array $configuration): void
    {
        $amqpConfig = $configuration['stage']['deploy']['QUEUE_CONFIGURATION']['amqp'] ?? null;
        if (!is_array($amqpConfig)) {
            return;
        }

        $user = isset($amqpConfig['user']) ? (string)$amqpConfig['user'] : '';
        $password = isset($amqpConfig['password']) ? (string)$amqpConfig['password'] : '';
        $host = isset($amqpConfig['host']) ? (string)$amqpConfig['host'] : 'rabbitmq';

        if ($user === '' || $password === '' || $user === 'guest' || $host !== 'rabbitmq') {
            return;
        }

        $innerCommand = sprintf(
            "rabbitmqctl add_user %s %s 2>/dev/null || rabbitmqctl change_password %s %s; " .
            "rabbitmqctl set_permissions -p / %s '.*' '.*' '.*'",
            escapeshellarg($user),
            escapeshellarg($password),
            escapeshellarg($user),
            escapeshellarg($password),
            escapeshellarg($user)
        );

        $command = sprintf(
            'docker-compose exec -T rabbitmq bash -lc %s',
            escapeshellarg($innerCommand)
        );

        $I->assertTrue($I->runBashCommand($command), 'Failed to configure RabbitMQ user for install validation');
    }

    /**
     * Test RabbitMQ wrong configuration
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider wrongConfigurationDataProvider
     */
    public function testWrongConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        if (isset($data['rabbitMqVersion'])) {
            $this->rabbitMqVersion = $data['rabbitMqVersion'];
        }

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
     * Test queue configuration without any message broker (uses DB)
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @return void
     * @throws \Robo\Exception\TaskException
     * @dataProvider noMessageBrokerDataProvider
     */
    public function testNoMessageBroker(\CliTester $I, \Codeception\Example $data): void
    {
        $this->serviceToAdd = 'none';

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

        $I->assertArrayHasKey('queue', $config, 'Queue configuration should be present');
        $I->assertArrayNotHasKey('amqp', $config['queue'], 'AMQP configuration should not be present (no RabbitMQ)');
        $I->assertArrayNotHasKey('stomp', $config['queue'], 'STOMP configuration should not be present (no ActiveMQ)');

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
     * Override prepareWorkplace to add RabbitMQ service based on test scenario
     *
     * @param \CliTester $I
     * @param string $templateVersion
     * @return void
     */
    protected function prepareWorkplace(\CliTester $I, string $templateVersion): void
    {
        parent::prepareWorkplace($I, $templateVersion);

        if ($this->serviceToAdd === 'rabbitmq') {
            $this->addRabbitMqService($I);
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
        $services = $I->readServicesYaml();

        if (!isset($services['rabbitmq'])) {
            $services['rabbitmq'] = [
                'type' => sprintf('rabbitmq:%s', $this->rabbitMqVersion),
                'disk' => 1024,
            ];
            $I->writeServicesYaml($services);
        }

        $app = $I->readAppMagentoYaml();

        if (!isset($app['relationships']['rabbitmq'])) {
            $app['relationships']['rabbitmq'] = 'rabbitmq:rabbitmq';
            $I->writeAppMagentoYaml($app);
        }
    }
}
