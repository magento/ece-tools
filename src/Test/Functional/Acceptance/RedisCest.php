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
 */
abstract class RedisCest extends AbstractCest
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
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
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
        $I->assertArrayNotHasKey('type', $config['cache']);

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    abstract protected function defaultConfigurationDataProvider(): array;

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider wrongConfigurationRedisBackendDataProvider
     */
    public function testWrongConfigurationRedisBackend(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

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
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider redisWrongConnectionDataProvider
     */
    public function testRedisWrongConnection(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s',
            $I->getExposedPort()
        ));

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
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider goodConfigurationDataProvider
     */
    public function testGoodConfiguration(\CliTester $I, \Codeception\Example $data): void
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
