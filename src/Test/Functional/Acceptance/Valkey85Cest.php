<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Robo\Exception\TaskException;

/**
 * Checks Valkey configuration
 *
 * @group php85
 */
class Valkey85Cest extends ValkeyCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.9',
                'valkey_version' => '9.0',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationValkeyBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.4.9',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => 'TestValkeyModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The VALKEY_BACKEND variable contains an invalid value TestValkeyModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' valkey,'
                    . ' \Magento\Framework\Cache\Backend\Valkey,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache,'
                    . ' symfony_l2.',
                'errorDeployMessage' => '',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function valkeyWrongConnectionDataProvider(): array
    {
        return [
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\Magento\Framework\Cache\Backend\Redis',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        '_custom_valkey_backend' => true,
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Verifies that symfony_l2 generates the full two-frontend structure in env.php on Magento 2.4.9.
     *
     * @param CliTester $I
     * @throws TaskException
     */
    public function testSymfonyL2Configuration(CliTester $I): void
    {
        $this->prepareWorkplace($I, '2.4.9');
        $I->generateDockerCompose(
            sprintf('--mode=production --expose-db-port=%s', $I->getExposedPort())
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->writeEnvMagentoYaml([
            'stage' => [
                'deploy' => [
                    'VALKEY_BACKEND' => 'symfony_l2',
                ],
            ],
        ]);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase was failed');

        $config = $this->getConfig($I);
        $this->assertSymfonyL2Frontends($config, $I);
        $this->assertSymfonyL2TypeMappings($config, $I);
        $this->assertSymfonyL2LuaOptions($config, $I);
    }

    /**
     * Verifies that VALKEY_USE_SLAVE_CONNECTION populates 'load_from_slave' in remote_backend_options for
     * both symfony_l2 frontends, and that the deploy still succeeds with it present.
     *
     * The 'valkey-slave' relationship cannot point back at the same 'valkey'/'cache' service:
     * magento-cloud-docker's build:compose (CloudSource::addRelationships()) rejects a second relationship
     * mapping to the same canonical service type ("Only one instance of service ... supported"). Its
     * service-name map treats 'redis' and 'cache'/'valkey' as two INDEPENDENT canonical types, so a real,
     * separate Redis service (named 'redis') is added purely to serve as a live, connectable
     * 'valkey-slave' target - this is a genuinely distinct container, not a self-reference, and doesn't
     * conflict with the primary 'valkey' service/relationship used for the actual cache backend.
     *
     * Separately, magento-cloud-docker's local Relationship::get() (which produces the container's
     * MAGENTO_CLOUD_RELATIONSHIPS) only emits entries for its fixed, hardcoded service map (redis, valkey,
     * database, etc.) - it silently ignores custom relationship names declared in .magento.app.yaml, so
     * 'valkey-slave' never reaches the container through the normal generation path.
     * injectValkeySlaveRelationship() works around that by patching the generated .docker/config.env
     * directly, after generateDockerCompose() writes it and before any container starts.
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
        $services['redis'] = ['type' => 'redis:7.2'];
        $I->writeServicesYaml($services);

        $app = $I->readAppMagentoYaml();
        $app['relationships']['valkey-slave'] = 'redis:redis';
        $I->writeAppMagentoYaml($app);

        $I->generateDockerCompose(
            sprintf('--mode=production --expose-db-port=%s', $I->getExposedPort())
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);
        $this->injectValkeySlaveRelationship($I);

        $I->writeEnvMagentoYaml([
            'stage' => [
                'deploy' => [
                    'VALKEY_BACKEND' => 'symfony_l2',
                    'VALKEY_USE_SLAVE_CONNECTION' => true,
                ],
            ],
        ]);

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase was failed');
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase was failed');

        $config = $this->getConfig($I);

        foreach (['default', 'stale_cache_enabled'] as $frontendName) {
            $remoteOptions = $config['cache']['frontend'][$frontendName]['backend_options']['remote_backend_options'];
            $I->assertSame(
                'redis',
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
     * Injects the 'valkey-slave' relationship into the generated MAGENTO_CLOUD_RELATIONSHIPS.
     *
     * magento-cloud-docker's local Relationship::get() only emits entries for its fixed, hardcoded
     * service map (redis, valkey, database, etc.) - it ignores custom relationship names declared in
     * .magento.app.yaml, so 'valkey-slave' never reaches the container's MAGENTO_CLOUD_RELATIONSHIPS env
     * var through the normal generation path. This patches the generated .docker/config.env (loaded into
     * every service via docker-compose's `env_file`) using the same base64(json_encode()) format the
     * vendor tool itself uses for that variable.
     *
     * Must run after generateDockerCompose() - that's what writes .docker/config.env - and before
     * startEnvironment(), since env_file is only read when containers start.
     *
     * @param CliTester $I
     */
    private function injectValkeySlaveRelationship(CliTester $I): void
    {
        $configEnvPath = $I->getWorkDirPath() . DIRECTORY_SEPARATOR . '.docker' . DIRECTORY_SEPARATOR . 'config.env';
        $lines = file($configEnvPath, FILE_IGNORE_NEW_LINES);

        foreach ($lines as $index => $line) {
            if (strpos($line, 'MAGENTO_CLOUD_RELATIONSHIPS=') !== 0) {
                continue;
            }

            $relationships = json_decode(
                base64_decode(substr($line, strlen('MAGENTO_CLOUD_RELATIONSHIPS='))),
                true
            );
            $relationships['valkey-slave'] = [['host' => 'redis', 'port' => '6379']];
            $lines[$index] = 'MAGENTO_CLOUD_RELATIONSHIPS=' . base64_encode(json_encode($relationships));
            break;
        }

        file_put_contents($configEnvPath, implode(PHP_EOL, $lines) . PHP_EOL);
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
                    'remote_backend' => 'valkey',
                    'remote_backend_options' => [
                        'server'          => 'cache',
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
                    'remote_backend' => 'valkey',
                    'remote_backend_options' => [
                        'server'          => 'cache',
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

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'cache',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.9',
                'valkey_version' => '9.0',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'cache',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedBackend' => '\CustomValkeyModel',
                'expectedConfig' => [],
            ],
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => 'valkey',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Valkey',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'cache',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.9',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
                        'remote_backend_options' => [
                            'persistent' => 0,
                            'server' => 'cache',
                            'database' => 1,
                            'port' => '6379',
                            'password' => '',
                            'compress_data' => '1',
                        ],
                        'local_backend' => 'Cm_Cache_Backend_File',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/',
                        ],
                    ],
                ],
            ],
        ];
    }
}
