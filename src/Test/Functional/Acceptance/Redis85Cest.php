<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Robo\Exception\TaskException;

/**
 * Checks Redis configuration
 *
 * @group php85
 */
class Redis85Cest extends RedisCest
{
    /**
     * Legacy Redis cache backends are not supported on Magento 2.4.9+, so only symfony_l2 is exercised here.
     *
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @skip
     */
    public function testDefaultConfiguration(CliTester $I, Example $data): void
    {
        return;
    }

    /**
     * @return array
     */
    protected function wrongConfigurationRedisBackendDataProvider(): array
    {
        return [];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @skip
     */
    public function testWrongConfigurationRedisBackend(CliTester $I, Example $data): void
    {
        return;
    }

    /**
     * @return array
     */
    protected function redisWrongConnectionDataProvider(): array
    {
        return [];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @skip
     */
    public function testRedisWrongConnection(CliTester $I, Example $data): void
    {
        return;
    }

    /**
     * @return array
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @skip
     */
    public function testGoodConfiguration(CliTester $I, Example $data): void
    {
        return;
    }

    /**
     * Verifies that symfony_l2 generates the full two-frontend structure in env.php on Magento 2.4.9
     * when backed by a Redis service.
     *
     * @param CliTester $I
     * @throws TaskException
     */
    public function testSymfonyL2Configuration(CliTester $I): void
    {
        $this->prepareWorkplace($I, '2.4.9');

        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml([
            'stage' => [
                'deploy' => [
                    'REDIS_BACKEND' => 'symfony_l2',
                ],
            ],
        ]);

        $I->assertTrue(
            $I->runDockerComposeCommand('run build cloud-build'),
            'Build phase was failed'
        );

        $I->assertTrue(
            $I->startEnvironment(),
            'Docker could not start'
        );

        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-deploy'),
            'Deploy phase was failed'
        );

        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-post-deploy'),
            'Post Deploy phase was failed'
        );

        $config = $this->getConfig($I);

        $this->assertSymfonyL2Frontends($config, $I);
        $this->assertSymfonyL2TypeMappings($config, $I);
        $this->assertSymfonyL2LuaOptions($config, $I);
    }

    /**
     * Verifies that REDIS_USE_SLAVE_CONNECTION populates 'load_from_slave' in remote_backend_options for
     * both symfony_l2 frontends, and that the deploy still succeeds with it present.
     *
     * The 'redis-slave' relationship cannot point back at the same 'redis' service: magento-cloud-docker's
     * build:compose (CloudSource::addRelationships()) rejects a second relationship mapping to the same
     * canonical service type ("Only one instance of service ... supported"). Its service-name map treats
     * 'redis' and 'cache'/'valkey' as two INDEPENDENT canonical types, so a real, separate Valkey service
     * (named 'cache') is added purely to serve as a live, connectable 'redis-slave' target - this is a
     * genuinely distinct container, not a self-reference, and doesn't conflict with the primary 'redis'
     * service/relationship used for the actual cache backend.
     *
     * @param CliTester $I
     * @throws TaskException
     */
    public function testSymfonyL2SlaveConnectionConfiguration(CliTester $I): void
    {
        $this->prepareWorkplace($I, '2.4.9');

        // Must happen before generateDockerCompose() - that's what bakes .magento.app.yaml's
        // relationships into the generated docker-compose.yml (MAGENTO_CLOUD_RELATIONSHIPS); editing
        // it afterward has no effect on the already-generated compose file.
        $services = $I->readServicesYaml();
        $services['cache'] = ['type' => 'valkey:9.0'];
        $I->writeServicesYaml($services);

        $app = $I->readAppMagentoYaml();
        $app['relationships']['redis-slave'] = 'cache:valkey';
        $I->writeAppMagentoYaml($app);

        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml([
            'stage' => [
                'deploy' => [
                    'REDIS_BACKEND' => 'symfony_l2',
                    'REDIS_USE_SLAVE_CONNECTION' => true,
                ],
            ],
        ]);

        $I->assertTrue(
            $I->runDockerComposeCommand('run build cloud-build'),
            'Build phase was failed'
        );

        $I->assertTrue(
            $I->startEnvironment(),
            'Docker could not start'
        );

        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-deploy'),
            'Deploy phase was failed'
        );

        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-post-deploy'),
            'Post Deploy phase was failed'
        );

        $config = $this->getConfig($I);

        foreach (['default', 'stale_cache_enabled'] as $frontendName) {
            $remoteOptions = $config['cache']['frontend'][$frontendName]['backend_options']['remote_backend_options'];
            $I->assertSame(
                'cache',
                $remoteOptions['load_from_slave']['server'] ?? null,
                "Wrong load_from_slave server for '$frontendName' frontend"
            );
            $I->assertSame(
                '6379',
                (string)($remoteOptions['load_from_slave']['port'] ?? null),
                "Wrong load_from_slave port for '$frontendName' frontend"
            );
            $I->assertSame(1, $remoteOptions['retry_reads_on_master'] ?? null);
        }

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Assert both symfony_l2 frontends are generated with expected options.
     *
     * @param array $config
     * @param CliTester $I
     */
    private function assertSymfonyL2Frontends(array $config, CliTester $I): void
    {
        $I->assertSame('symfony_l2', $config['cache']['frontend']['default']['backend'], 'Wrong default backend');
        $I->assertSame(
            'symfony_l2',
            $config['cache']['frontend']['stale_cache_enabled']['backend'],
            'Wrong stale_cache_enabled backend'
        );

        $this->checkArraySubset(
            [
                'backend_options' => [
                    'remote_backend' => 'redis',
                    'remote_backend_options' => [
                        'server'          => 'redis',
                        'port'            => '6379',
                        'database'        => 1,
                        'compression_lib' => 'gzip',
                        'persistent_id'   => 'magento_l2_default',
                    ],
                    'local_backend'         => 'file',
                    'local_backend_options' => ['cache_dir' => '/dev/shm/magento_l1'],
                ],
            ],
            $config['cache']['frontend']['default'],
            $I
        );
        $I->assertArrayNotHasKey('use_stale_cache', $config['cache']['frontend']['default']['backend_options']);

        $this->checkArraySubset(
            [
                'backend_options' => [
                    'remote_backend' => 'redis',
                    'remote_backend_options' => [
                        'server'          => 'redis',
                        'port'            => '6379',
                        'database'        => 1,
                        'compression_lib' => 'gzip',
                        'persistent_id'   => 'magento_l2_stale',
                    ],
                    'local_backend'         => 'file',
                    'local_backend_options' => ['cache_dir' => '/dev/shm/magento_l1_stale'],
                    'use_stale_cache'       => true,
                ],
            ],
            $config['cache']['frontend']['stale_cache_enabled'],
            $I
        );
    }

    /**
     * Assert cache type to frontend mappings for symfony_l2 layout.
     *
     * @param array $config
     * @param CliTester $I
     */
    private function assertSymfonyL2TypeMappings(array $config, CliTester $I): void
    {
        $expectedTypes = [
            'default' => 'default',
            'layout' => 'stale_cache_enabled',
            'block_html' => 'stale_cache_enabled',
            'reflection' => 'stale_cache_enabled',
            'config_integration' => 'stale_cache_enabled',
            'config_integration_api' => 'stale_cache_enabled',
            'full_page' => 'stale_cache_enabled',
            'translate' => 'stale_cache_enabled',
        ];

        foreach ($expectedTypes as $cacheType => $frontendName) {
            $I->assertSame(
                $frontendName,
                $config['cache']['type'][$cacheType]['frontend'] ?? null,
                sprintf('Wrong frontend mapping for cache type "%s"', $cacheType)
            );
        }
    }

    /**
     * Assert LUA options land in remote_backend_options (default USE_LUA=false, USE_LUA_ON_GC=true)
     * and never leak into the outer backend_options for symfony_l2.
     *
     * @param array $config
     * @param CliTester $I
     */
    private function assertSymfonyL2LuaOptions(array $config, CliTester $I): void
    {
        foreach (['default', 'stale_cache_enabled'] as $frontendName) {
            $backendOptions = $config['cache']['frontend'][$frontendName]['backend_options'];
            $I->assertArrayNotHasKey('use_lua', $backendOptions);
            $I->assertArrayNotHasKey('_useLua', $backendOptions);
            $I->assertSame('0', $backendOptions['remote_backend_options']['use_lua']);
            $I->assertSame('1', $backendOptions['remote_backend_options']['use_lua_on_gc']);
        }
    }
}
