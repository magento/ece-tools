<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Exception;

/**
 *  Checks split database wizard functionality
 */
class SplitDbWizardCest extends AbstractCest
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
     * @dataProvider dataProviderMagentoCloudVersions
     * @throws Exception
     */
    public function testSplitDbWizard(CliTester $I, Example $data)
    {
        $this->prepareWorkplace($I, $data['version']);
        $envMagento = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];
        $I->writeEnvMagentoYaml($envMagento);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->runDockerComposeCommand('run build cloud-build');

        // Running 'Split Db' wizard in an environment without Split Db architecture
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput([
            'DB is not split',
            '- DB cannot be split on this environment'
        ]);

        // Deploy 'Split Db' architecture
        $services = $I->readServicesYaml();
        $appMagento = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $appMagento['relationships']['database-quote'] = 'mysql-quote:mysql';
        $appMagento['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($appMagento);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        // Running 'Split Db' wizard in an environment with Split Db architecture and not splitting Magento Db
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput([
            'DB is not split',
            '- You may split DB using SPLIT_DB variable in .magento.env.yaml file'
        ]);

        // Running 'Split Db' wizard in an environment with Split Db architecture
        // and splitting `quote` tables of Magento Db
        $envMagento['stage']['deploy']['SPLIT_DB'][] = 'quote';
        $I->writeEnvMagentoYaml($envMagento);
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput('DB is already split with type(s): quote');

        // Running 'Split Db' wizard in an environment with Split Db architecture
        // and splitting `quote` and `sales` tables of Magento Db
        $envMagento['stage']['deploy']['SPLIT_DB'][] = 'sales';
        $I->writeEnvMagentoYaml($envMagento);
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput('DB is already split with type(s): quote, sales');
    }

    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => 'master'],
            ['version' => '2.3.4'],
            ['version' => '2.2.11'],
            ['version' => '2.1.18'],
        ];
    }
}
