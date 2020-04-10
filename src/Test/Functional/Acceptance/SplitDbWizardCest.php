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
     * @throws Exception
     */
    public function testEnvWithoutSplitDbArchitecture(CliTester $I)
    {
        $this->prepareWorkplace($I, 'master');
        $I->writeEnvMagentoYaml(['stage' => ['global' => ['SCD_ON_DEMAND' => true]]]);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput([
            'DB is not split',
            '- DB cannot be split on this environment'
        ]);
    }


    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProviderMagentoCloudVersions
     * @throws Exception
     */
    public function testEnvWithSplitDbArchitecture(CliTester $I, Example $data)
    {
        $this->prepareWorkplace($I, $data['version']);
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $magentoEnv = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->writeEnvMagentoYaml($magentoEnv);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput([
            'DB is not split',
            '- You may split DB using SPLIT_DB variable in .magento.env.yaml file'
        ]);
        $magentoEnv['stage']['deploy']['SPLIT_DB'][] = 'quote';
        $I->writeEnvMagentoYaml($magentoEnv);
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput('DB is already split with type(s): quote');
        $magentoEnv['stage']['deploy']['SPLIT_DB'][] = 'sales';
        $I->writeEnvMagentoYaml($magentoEnv);
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
//            ['version' => 'master'],
//            ['version' => '2.3.4'],
            ['version' => '2.3.3'],
//            ['version' => '2.2.11'],
//            ['version' => '2.1.18'],
        ];
    }
}
