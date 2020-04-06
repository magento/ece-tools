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
 *  Checks Split Database Wizard
 */
class SplitDbWizardCest extends AbstractCest
{
    /**
     * @param CliTester $I
     * @throws TaskException
     */
    public function testEnvWithoutSplitDbArchitecture(CliTester $I)
    {
        $I->assertTrue($I->runEceDockerCommand('build:compose --mode=production'));
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state'));
        $I->seeInOutput([
            'DB is not split',
            '- DB cannot be split on this environment'
        ]);
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProviderTestEnvWithSplitDbArchitecture
     * @throws TaskException
     */
    public function testEnvWithSplitDbArchitecture(CliTester $I, Example $data)
    {
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->runEceDockerCommand('build:compose --mode=production');
        $envMagentoYamlData = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];
        foreach ($data['types'] as $type) {
            $envMagentoYamlData['stage']['deploy']['SPLIT_DB'][] = $type;
        }
        $I->writeEnvMagentoYaml($envMagentoYamlData);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state'));
        $I->seeInOutput($data['messages']);
    }

    /**
     * @return array
     */
    protected function dataProviderTestEnvWithSplitDbArchitecture(): array
    {
        return [
            [
                'types' => [],
                'messages' => [
                    'DB is not split',
                    '- You may split DB using SPLIT_DB variable in .magento.env.yaml file'
                ]
            ],
            [
                'types' => ['quote'],
                'messages' => ['DB is already split with type(s): quote',]
            ],
            [
                'types' => ['quote', 'sales'],
                'messages' => ['DB is already split with type(s): quote, sales']
            ]
        ];
    }
}
