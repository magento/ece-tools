<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;
use Robo\Exception\TaskException;

/**
 * Checks Redis configuration
 *
 */
abstract class RedisCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(CliTester $I): void
    {
        //Do nothing...
    }

    /**
     * @param CliTester $I
     * @return array
     */
    protected function getConfig(CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        return require $destination;
    }

    /**
     * Override prepareWorkplace to replace Valkey with Redis service for 2.4.8+ templates
     *
     * @param CliTester $I
     * @param string    $templateVersion
     * @return void
     */
    protected function prepareWorkplace(CliTester $I, string $templateVersion): void
    {
        parent::prepareWorkplace($I, $templateVersion);

        // Skip automatic replacement if child class manages cache service manually
        if (property_exists($this, 'cacheServiceToAdd')) {
            return;
        }

        // Replace Valkey with Redis for Magento 2.4.8+ templates (have Valkey by default)
        if (version_compare($templateVersion, '2.4.8', '>=')) {
            $this->replaceValkeyWithRedis($I);
        }
    }

    /**
     * Replace Valkey service with Redis service in services.yaml and .magento.app.yaml
     *
     * @param CliTester $I
     * @return void
     */
    protected function replaceValkeyWithRedis(CliTester $I): void
    {
        // Read current services.yaml
        $services = $I->readServicesYaml();

        // Remove Valkey service if present
        if (isset($services['valkey'])) {
            unset($services['valkey']);
        }
        if (isset($services['cache'])) {
            unset($services['cache']);
        }

        // Add Redis service
        $services['redis'] = [
            'type' => 'redis:7.2'
        ];

        $I->writeServicesYaml($services);

        // Read current .magento.app.yaml
        $app = $I->readAppMagentoYaml();

        // Remove Valkey/cache relationship if present
        if (isset($app['relationships']['cache'])) {
            unset($app['relationships']['cache']);
        }
        if (isset($app['relationships']['valkey'])) {
            unset($app['relationships']['valkey']);
        }

        // Add Redis relationship
        $app['relationships']['redis'] = 'redis:redis';

        $I->writeAppMagentoYaml($app);
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);

        $I->assertSame(
            'Cm_Cache_Backend_Redis',
            $config['cache']['frontend']['default']['backend'],
            'Wrong backend model'
        );
        $this->checkArraySubset(
            [
                'backend_options' => [
                    'server' => 'redis',
                    'port' => '6379',
                    'database' => 1,
                ]
            ],
            $config['cache']['frontend']['default'],
            $I
        );
        $I->assertSame(
            'Cm_Cache_Backend_Redis',
            $config['cache']['frontend']['page_cache']['backend'],
            'Wrong backend model'
        );
        $this->checkArraySubset(
            [
                'backend_options' => [
                    'server' => 'redis',
                    'port' => '6379',
                    'database' => 2,
                ]
            ],
            $config['cache']['frontend']['page_cache'],
            $I
        );

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    abstract protected function defaultConfigurationDataProvider(): array;

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider wrongConfigurationRedisBackendDataProvider
     */
    public function testWrongConfigurationRedisBackend(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml($data['wrongConfiguration']);

        $I->assertSame($data['buildSuccess'], $I->runDockerComposeCommand('run build cloud-build'));
        $I->seeInOutput($data['errorBuildMessage']);
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertSame($data['deploySuccess'], $I->runDockerComposeCommand('run build cloud-deploy'));
        $I->seeInOutput($data['errorDeployMessage']);
    }

    /**
     * @return array
     */
    abstract protected function wrongConfigurationRedisBackendDataProvider(): array;

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider redisWrongConnectionDataProvider
     */
    public function testRedisWrongConnection(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertFalse($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was successful');
    }

    /**
     * @return array
     */
    abstract protected function redisWrongConnectionDataProvider(): array;

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider goodConfigurationDataProvider
     */
    public function testGoodConfiguration(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post Deploy phase was failed');

        $config = $this->getConfig($I);
        $I->assertSame(
            $data['expectedBackend'],
            $config['cache']['frontend']['default']['backend'],
            'Wrong backend model'
        );

        $this->checkArraySubset(
            $data['expectedConfig'],
            $config['cache']['frontend']['default'],
            $I
        );

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    abstract protected function goodConfigurationDataProvider(): array;
}
