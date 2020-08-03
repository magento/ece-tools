<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Checks Redis configuration
 *
 * @group php74
 */
class RedisCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        //Do nothing...
    }

    /**
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
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

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
        $I->assertArraySubset(
            [
                'backend_options' => [
                    'server' => 'redis',
                    'port' => '6379',
                    'database' => 1,
                ]
            ],
            $config['cache']['frontend']['default']
        );
        $I->assertSame(
            'Cm_Cache_Backend_Redis',
            $config['cache']['frontend']['page_cache']['backend'],
            'Wrong backend model'
        );
        $I->assertArraySubset(
            [
                'backend_options' => [
                    'server' => 'redis',
                    'port' => '6379',
                    'database' => 2,
                ]
            ],
            $config['cache']['frontend']['page_cache']
        );
        $I->assertArrayNotHasKey('type', $config['cache']);

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => 'master',
            ],
        ];
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider wrongConfigurationDataProvider
     */
    public function testWrongConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['wrongConfiguration']);

        $I->assertSame($data['buildSuccess'], $I->runDockerComposeCommand('run build cloud-build'));
        $I->seeInOutput($data['errorBuildMessage']);

        $I->assertSame($data['deploySuccess'], $I->runDockerComposeCommand('run build cloud-deploy'));
        $I->seeInOutput($data['errorDeployMessage']);
    }

    /**
     * @return array
     */
    protected function wrongConfigurationDataProvider(): array
    {
        return [
            [
                'version' => 'master',
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
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider goodConfigurationDataProvider
     */
    public function testGoodConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

        $I->writeEnvMagentoYaml($data['backendModel']);

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

        $I->assertArraySubset(
            $data['expectedConfig'],
            $config['cache']['frontend']['default']
        );

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [
            [
                'version' => 'master',
                'backendModel' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => 'master',
                'backendModel' => [
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
}
