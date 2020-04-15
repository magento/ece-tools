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
use Exception;

/**
 * Checks split database functionality
 */
class SplitDbCest extends AbstractCest
{
    /**
     * {@inheritDoc}
     * @param CliTester $I
     */
    public function _before(CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderMagentoCloudVersions
     */
    public function testSplitDb(CliTester $I, Example $data)
    {
        $this->prepareWorkplace($I, $data['version']);
        $envMagento = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];

        // Deploy 'Split Db' in an environment without prepared architecture
        $this->partWithoutSplitDbArch($I, $envMagento);

        $I->stopEnvironment(true);

        // Prepare config for deploy
        $this->prepareConfigToDeploySplitDb($I);

        // Deploy 'Split Db' in an environment with prepared architecture. Case with upgrade
        $this->partWithSplitDbArch($I, $envMagento);

        $I->stopEnvironment();

        // Install with Split db
        $this->partInstallWithSplitDb($I, $envMagento);
    }

    /**
     * Deploy 'Split Db' in an environment without prepared architecture
     *
     * @param CliTester $I
     * @param array $envMagento
     * @throws TaskException
     */
    private function partWithoutSplitDbArch(CliTester $I, array $envMagento)
    {
        $I->runEceDockerCommand('build:compose --mode=production');

        // Deploy 'Split Db' with the wrong Split Db type
        $envMagento['stage']['deploy']['SPLIT_DB'] = 'quote';
        $I->writeEnvMagentoYaml($envMagento);

        $I->runDockerComposeCommand('run build cloud-build');
        $I->seeInOutput([
            'ERROR: Fix configuration with given suggestions:',
            '- Environment configuration is not valid.',
            'Correct the following items in your .magento.env.yaml file:',
            'The SPLIT_DB variable contains an invalid value of type string. Use the following type: array.',
        ]);

        $I->stopEnvironment();

        $envMagento['stage']['deploy']['SPLIT_DB'] = ['checkout'];
        $I->writeEnvMagentoYaml($envMagento);
        $I->runDockerComposeCommand('run build cloud-build');
        $I->seeInOutput([
            'ERROR: Fix configuration with given suggestions:',
            '- Environment configuration is not valid.',
            'Correct the following items in your .magento.env.yaml file:',
            'The SPLIT_DB variable contains the invalid value.',
            'It should be array with next available values: [quote, sales].'
        ]);

        $I->stopEnvironment();

        $envMagento['stage']['deploy']['SPLIT_DB'] = ['quote', 'checkout'];
        $I->writeEnvMagentoYaml($envMagento);
        $I->runDockerComposeCommand('run build cloud-build');
        $I->seeInOutput([
            'ERROR: Fix configuration with given suggestions:',
            '- Environment configuration is not valid.'
            , 'Correct the following items in your .magento.env.yaml file:',
            'The SPLIT_DB variable contains the invalid value.'
            , 'It should be array with next available values: [quote, sales].',
        ]);

        $I->stopEnvironment();

        // Deploy 'Split Db' with the unavailable Split Db types
        $envMagento['stage']['deploy']['SPLIT_DB'] = ['quote', 'sales'];
        $I->writeEnvMagentoYaml($envMagento);
        $this->runDeploy($I);
        $I->seeInOutput(
            'ERROR: Enabling a split database will be skipped.'
            . ' Relationship do not have configuration for next types: sales, quote'
        );
        $this->checkEnvPhpConfig($I, [], ['checkout', 'sales']);
        $this->checkMagentoFront($I);
    }

    /**
     * @param CliTester $I
     * @param array $envMagento
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws TaskException
     */
    private function partWithSplitDbArch(CliTester $I, array $envMagento)
    {
        $I->runEceDockerCommand('build:compose --mode=production');

        // Run splitting database for 'quote' tables
        $envMagento['stage']['deploy']['SPLIT_DB'] = ['quote'];
        $I->writeEnvMagentoYaml($envMagento);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->seeInOutput([
            'INFO: Quote tables were split to DB magento2 in db-quote',
            'INFO: Running setup upgrade.',
        ]);
        $this->checkEnvPhpConfig($I, ['checkout'], ['sales']);
        $this->checkMagentoFront($I);

        $I->stopEnvironment(true);

        // 'Split Db' type was deleted
        unset($envMagento['stage']['deploy']);
        $I->writeEnvMagentoYaml($envMagento);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->seeInOutput('WARNING: Variable SPLIT_DB does not have data which were already split types: quote');
        $this->checkEnvPhpConfig($I, ['checkout'], ['sales']);
        $this->checkMagentoFront($I);

        $I->stopEnvironment(true);

        // 'Split Db' current type was deleted and new type added
        $envMagento['stage']['deploy']['SPLIT_DB'] = ['sales'];
        $I->writeEnvMagentoYaml($envMagento);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->seeInOutput('WARNING: Variable SPLIT_DB does not have data which were already split types: quote');
        $this->checkEnvPhpConfig($I, ['checkout'], ['sales']);
        $this->checkMagentoFront($I);

        $I->stopEnvironment(true);

        // 'Split Db' current type was returned
        $envMagento['stage']['deploy']['SPLIT_DB'] = ['sales', 'quote'];
        $I->writeEnvMagentoYaml($envMagento);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->seeInOutput([
            'INFO: Sales tables were split to DB magento2 in db-sales',
            'INFO: Running setup upgrade.',
        ]);
        $this->checkEnvPhpConfig($I, ['checkout', 'sales']);
        $this->checkMagentoFront($I);
    }

    /**
     * @param CliTester $I
     * @param array $envMagento
     * @throws Exception
     */
    private function partInstallWithSplitDb(CliTester $I, array $envMagento): void
    {
        $envMagento['stage']['deploy']['SPLIT_DB'] = ['sales'];
        $I->writeEnvMagentoYaml($envMagento);
        $this->runDeploy($I);
        $I->seeInOutput([
            'INFO: Sales tables were split to DB magento2 in db-sales',
            'INFO: Running setup upgrade.',
        ]);
        $this->checkEnvPhpConfig($I, ['sales'], ['checkout']);
        $this->checkMagentoFront($I);

        $I->stopEnvironment();

        $envMagento['stage']['deploy']['SPLIT_DB'] = ['quote', 'sales'];
        $I->writeEnvMagentoYaml($envMagento);
        $this->runDeploy($I);
        $I->seeInOutput([
            'INFO: Quote tables were split to DB magento2 in db-quote',
            'INFO: Running setup upgrade.',
            'INFO: Sales tables were split to DB magento2 in db-sales',
            'INFO: Running setup upgrade.',
        ]);
        $this->checkEnvPhpConfig($I, ['checkout', 'sales']);
        $this->checkMagentoFront($I);
    }

    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => 'master'],
            ['version' => '2.3.4'],
        ];
    }

    /**
     * @param CliTester $I
     */
    private function prepareConfigToDeploySplitDb(CliTester $I)
    {
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
    }

    /**
     * @param CliTester $I
     */
    private function checkMagentoFront(CliTester $I)
    {
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param CliTester $I
     * @param array $exists
     * @param array $notExist
     */
    private function checkEnvPhpConfig(CliTester $I, array $exists = [], array $notExist = [])
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
        $config = require $destination;

        foreach ($exists as $item) {
            $I->assertArrayHasKey($item, $config['db']['connection']);
            $I->assertArrayHasKey($item, $config['resource']);
        }
        foreach ($notExist as $item) {
            $I->assertArrayNotHasKey($item, $config['db']['connection']);
            $I->assertArrayNotHasKey($item, $config['resource']);
        }
    }

    /**
     * @param CliTester $I
     */
    private function runDeploy(CliTester $I)
    {
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
    }
}
