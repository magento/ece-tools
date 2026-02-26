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
 * Checks Valkey configuration
 */
abstract class ValkeyCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(CliTester $I): void
    {
        //Do nothing...
    }

    /**
     * @param  CliTester $I
     * @return array
     */
    protected function getConfig(CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        return include $destination;
    }

    /**
     * Override prepareWorkplace to replace Redis with Valkey service
     *
     * @param CliTester $I
     * @param string    $templateVersion
     * @return void
     */
    protected function prepareWorkplace(CliTester $I, string $templateVersion): void
    {
        parent::prepareWorkplace($I, $templateVersion);

        // Check if replacement is needed by examining actual services
        $services = $I->readServicesYaml();
        
        // Only replace if Redis exists and Valkey/cache doesn't exist
        $hasRedis = isset($services['redis']);
        $hasValkey = isset($services['valkey']) || isset($services['cache']);
        
        if ($hasRedis && !$hasValkey) {
            // Template has Redis but not Valkey, so replace it
            $this->replaceRedisWithValkey($I);
        }
        // If template already has Valkey (2.4.8+), do nothing
    }

    /**
     * Replace Redis service with Valkey service in services.yaml and .magento.app.yaml
     *
     * @param CliTester $I
     * @return void
     */
    protected function replaceRedisWithValkey(CliTester $I): void
    {
        // Read current services.yaml
        $services = $I->readServicesYaml();

        // Remove Redis service if present
        if (isset($services['redis'])) {
            unset($services['redis']);
        }

        // Add Valkey service
        $services['valkey'] = [
            'type' => 'valkey:8.0'
        ];

        $I->writeServicesYaml($services);

        // Read current .magento.app.yaml
        $app = $I->readAppMagentoYaml();

        // Remove Redis relationship if present
        if (isset($app['relationships']['redis'])) {
            unset($app['relationships']['redis']);
        }

        // Add Valkey relationship (use 'redis' as the relationship name for compatibility)
        // Magento expects 'redis' relationship name, but it will connect to Valkey
        $app['relationships']['redis'] = 'valkey:valkey';

        $I->writeAppMagentoYaml($app);
    }

    /**
     * @param        CliTester $I
     * @param        Example $data
     * @throws       TaskException
     * @dataProvider defaultConfigurationDataProvider
     */
    public function testDefaultConfiguration(CliTester $I, Example $data): void
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
     * @param        CliTester $I
     * @param        Example $data
     * @throws       TaskException
     * @dataProvider wrongConfigurationValkeyBackendDataProvider
     */
    public function testWrongConfigurationValkeyBackend(CliTester $I, Example $data): void
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
        $I->seeInOutput($data['errorBuildMessage']);
        $I->assertTrue($I->startEnvironment(), 'Docker could not start');
        $I->assertSame($data['deploySuccess'], $I->runDockerComposeCommand('run build cloud-deploy'));
        $I->seeInOutput($data['errorDeployMessage']);
    }

    /**
     * @return array
     */
    abstract protected function wrongConfigurationValkeyBackendDataProvider(): array;

    /**
     * @param        CliTester           $I
     * @param        Example $data
     * @throws       TaskException
     * @dataProvider valkeyWrongConnectionDataProvider
     */
    public function testValkeyWrongConnection(CliTester $I, Example $data): void
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
        $I->assertFalse($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase was successful');
    }

    /**
     * @return array
     */
    abstract protected function valkeyWrongConnectionDataProvider(): array;

    /**
     * @param        CliTester $I
     * @param        Example $data
     * @throws       TaskException
     * @dataProvider goodConfigurationDataProvider
     */
    public function testGoodConfiguration(CliTester $I, Example $data): void
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
