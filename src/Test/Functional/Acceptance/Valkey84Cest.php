<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks Valkey configuration
 *
 * @group php84
 */
class Valkey84Cest extends ValkeyCest
{
    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        //Do nothing...
    }

    /**
     * Change MySQL version from 11.4 to 10.8
     */
    private function setMySQLVersionTo108(\CliTester $I): void
    {
        $services = $I->readServicesYaml();
        $services['mysql']['type'] = 'mysql:10.8';
        $I->writeServicesYaml($services);
    }



    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider goodConfigurationDataProvider
     */
    public function testGoodConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $this->setMySQLVersionTo108($I);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['configuration']);

        $I->assertTrue($I->runDockerComposeCommand('run -e COMPOSER_IGNORE_PLATFORM_REQS=1 build cloud-build'), 'Build phase was failed');
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
     * @param \CliTester $I
     * @return array
     */
    private function getConfig(\CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, \Magento\CloudDocker\Test\Functional\Codeception\Docker::DEPLOY_CONTAINER));
        return require $destination;
    }

    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
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
    protected function wrongConfigurationValkeyBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.4.8',
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
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
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
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'valkey',
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
                'version' => '2.4.8',
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
                            'server' => 'valkey',
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
