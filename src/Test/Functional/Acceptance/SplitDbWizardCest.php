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
 *
 * @group php74
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
        $I->writeEnvMagentoYaml(['stage' => ['global' => ['SCD_ON_DEMAND' => true]]]);

        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');

        // Running 'Split Db' wizard in an environment without Split Db architecture
        $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
        $I->seeInOutput([
            'DB is not split',
            'DB cannot be split on this environment'
        ]);

        $I->stopEnvironment(true);

        // Deploy 'Split Db' architecture
        $services = $I->readServicesYaml();
        $appMagento = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $appMagento['relationships']['database-quote'] = 'mysql-quote:mysql';
        $appMagento['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($appMagento);
        // Restore app/etc after build phase
        $I->runDockerComposeCommand('run build bash -c "cp -r /app/init/app/etc /app/app"');
        $I->generateDockerCompose('--mode=production');

        foreach ($this->variationsData() as $variationData) {
            $this->setSplitDbTypesIntoMagentoEnvYaml($I, $variationData['splitDbTypes']);
            $this->runDeploy($I);
            $I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state');
            $I->seeInOutput($variationData['messages']);

            $I->stopEnvironment(true);
        }
    }

    private function variationsData(): array
    {
        return [
            'Running Split Db wizard in an environment with Split Db architecture and not splitting Magento Db' => [
                'splitDbTypes' => null,
                'messages' => [
                    'DB is not split',
                    'You may split DB using SPLIT_DB variable in .magento.env.yaml file'
                ]
            ],
            'Running Split Db wizard in an environment with Split Db architecture'
            . 'and splitting `quote` tables of Magento Db' => [
                'splitDbTypes' => ['quote'],
                'messages' => 'DB is already split with type(s): quote',
            ],
            'Running Split Db wizard in an environment with Split Db architecture'
            . 'and splitting `quote` and `sales` tables of Magento Db' => [
                'splitDbTypes' => ['quote', 'sales'],
                'messages' => 'DB is already split with type(s): quote, sales',
            ]
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.4.1'],
        ];
    }

    /**
     * @param CliTester $I
     * @param $splitTypes
     */
    private function setSplitDbTypesIntoMagentoEnvYaml(CliTester $I, $splitTypes = null)
    {
        $config = $I->readEnvMagentoYaml();
        if (null !== $splitTypes) {
            $config['stage']['deploy']['SPLIT_DB'] = $splitTypes;
        } else {
            unset($config['stage']['deploy']['SPLIT_DB']);
        }
        $I->writeEnvMagentoYaml($config);
    }

    /**
     * @param CliTester $I
     */
    private function runDeploy(CliTester $I)
    {
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
    }
}
