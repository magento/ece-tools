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
 * @group php84
 */
class Redis84Cest extends RedisCest
{
    private const CACHE_SERVICE_REDIS = 'redis';

    /**
     * Which cache service to add: 'redis', 'valkey', or 'none'
     *
     * @var string|null
     */
    protected ?string $cacheServiceToAdd = null;

    /**
     * Reset cache service property before each test
     *
     * @param CliTester $I
     * @return void
     */
    public function _before(CliTester $I): void
    {
        $this->cacheServiceToAdd = null;
    }

    /**
     * Ensure appropriate cache service/relationship are present
     *
     * @param CliTester $I
     * @param string    $templateVersion
     * @return void
     */
    protected function prepareWorkplace(CliTester $I, string $templateVersion): void
    {
        // Set default cache service if not specified by the test
        if ($this->cacheServiceToAdd === null) {
            $this->cacheServiceToAdd = self::CACHE_SERVICE_REDIS;
        }
        parent::prepareWorkplace($I, $templateVersion);

        if ($this->cacheServiceToAdd === self::CACHE_SERVICE_REDIS) {
            $this->removeValkeyIfSelected($I);
            $this->addRedisService($I);
        } elseif ($this->cacheServiceToAdd === 'valkey') {
            $this->addValkeyService($I);
        }
        // If 'none', don't add any cache service
    }

    /**
     * Add Redis service to services.yaml and relationship to .magento.app.yaml if missing
     *
     * @param CliTester $I
     * @return void
     */
    private function addRedisService(CliTester $I): void
    {
        // Ensure redis service exists
        $services = $I->readServicesYaml();
        if (!isset($services['redis'])) {
            $services['redis'] = [
                'type' => 'redis:7.0',
            ];
            $I->writeServicesYaml($services);
        }

        // Ensure redis relationship exists
        $app = $I->readAppMagentoYaml();
        if (!isset($app['relationships']['redis'])) {
            $app['relationships']['redis'] = 'redis:redis';
            $I->writeAppMagentoYaml($app);
        }
    }

    /**
     * Add Valkey service to services.yaml and relationship to .magento.app.yaml if missing
     *
     * @param CliTester $I
     * @return void
     */
    private function addValkeyService(CliTester $I): void
    {
        // Ensure only one Valkey instance exists by removing 'valkey' service and using 'cache' key
        $services = $I->readServicesYaml();
        $originalServices = $services;
        $services = array_diff_key($services, array_flip(['valkey']));
        $added = false;
        if (!isset($services['cache'])) {
            $services['cache'] = [
                'type' => 'valkey:8.0',
            ];
            $added = true;
        }
        if ($services !== $originalServices || $added) {
            $I->writeServicesYaml($services);
        }

        // Ensure relationship uses 'valkey' key mapped to 'cache:cache' and remove legacy/duplicates
        $app = $I->readAppMagentoYaml();
        $originalRelationships = $app['relationships'] ?? [];
        if (!isset($app['relationships']) || !is_array($app['relationships'])) {
            $app['relationships'] = [];
        }
        $app['relationships'] = array_diff_key(
            $app['relationships'],
            array_flip(['cache', 'valkey-slave', 'cache-slave'])
        );
        if (!isset($app['relationships']['valkey'])) {
            $app['relationships']['valkey'] = 'cache:cache';
        }
        if ($app['relationships'] !== $originalRelationships) {
            $I->writeAppMagentoYaml($app);
        }
    }

    /**
     * Remove Valkey services and relationships when Redis is selected.
     * This overrides the parent's hook so the parent will call this version.
     *
     * @param CliTester $I
     * @return void
     */
    protected function removeValkeyIfSelected(CliTester $I): void
    {
        if ($this->cacheServiceToAdd !== self::CACHE_SERVICE_REDIS) {
            return;
        }

        // Remove Valkey-related services
        $services = $I->readServicesYaml();
        $originalServices = $services;
        $services = array_diff_key($services, array_flip(['valkey', 'cache', 'cache-slave']));
        if ($services !== $originalServices) {
            $I->writeServicesYaml($services);
        }

        // Remove Valkey-related relationships
        $app = $I->readAppMagentoYaml();
        if (isset($app['relationships']) && is_array($app['relationships'])) {
            $originalRelationships = $app['relationships'];
            $app['relationships'] = array_diff_key(
                $app['relationships'],
                array_flip(['valkey', 'cache', 'valkey-slave', 'cache-slave', 'valkey-session', 'cache-session'])
            );
            if ($app['relationships'] !== $originalRelationships) {
                $I->writeAppMagentoYaml($app);
            }
        }
    }

    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
                'defaultConfiguration' => 'redis',
            ],
        ];
    }

    /**
     * Data provider for Valkey fallback test
     *
     * @return array
     */
    protected function fallbackToValkeyDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationRedisBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'TestRedisModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The REDIS_BACKEND variable contains an invalid value TestRedisModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function redisWrongConnectionDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
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
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        '_custom_redis_backend' => true,
                                        'backend' => '\CustomRedisModel',
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
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'Cm_Cache_Backend_Redis',
                        ],
                    ],
                ],
                'expectedBackend' => 'Cm_Cache_Backend_Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\CustomRedisModel',
                                        'backend_options' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedBackend' => '\CustomRedisModel',
                'expectedConfig' => [],
            ],
            [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
                        'remote_backend_options' => [
                            'persistent' => 0,
                            'server' => 'redis',
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

    /**
     * Fallback to Valkey when Redis is not configured but Valkey is available
     *
     * @param  CliTester           $I
     * @param  Example $data
     * @return void
     * @throws TaskException
     * @dataProvider fallbackToValkeyDataProvider
     */
    public function testFallbackToValkey(CliTester $I, Example $data): void
    {
        $this->cacheServiceToAdd = 'valkey';

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

        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue(
            $I->downloadFromContainer(
                '/app/etc/env.php',
                $destination,
                \Magento\CloudDocker\Test\Functional\Codeception\Docker::DEPLOY_CONTAINER
            )
        );
        $config = require $destination;

        // Default backend should still be Redis backend class, but host should come from Valkey ('cache')
        $I->assertSame(
            'Cm_Cache_Backend_Redis',
            $config['cache']['frontend']['default']['backend'],
            'Wrong backend model'
        );
        $this->checkArraySubset(
            [
                'backend_options' => [
                    'server' => 'cache',
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
                    'server' => 'cache',
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
}
