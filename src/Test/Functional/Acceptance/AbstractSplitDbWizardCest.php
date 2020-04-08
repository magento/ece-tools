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
 *  General test for Split Database Wizard
 */
abstract class AbstractSplitDbWizardCest extends AbstractCest
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
     * {@inheritDoc}
     * @param CliTester $I
     */
    public function _after(CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @var integer
     */
    private static $counter = 0;

    /**
     * @var boolean
     */
    private static $beforeShouldRun = true;

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderEnvWithoutSplitDbArchitecture
     */
    public function testEnvWithoutSplitDbArchitecture(CliTester $I, Example $data)
    {
        $this->prepareWorkplace($I, $data['version']);
        try {
            $I->assertTrue($I->runEceDockerCommand('build:compose --mode=production'));
            $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
            $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state'));
            $I->seeInOutput([
                'DB is not split',
                '- DB cannot be split on this environment'
            ]);
        } finally {
            parent::_after($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderEnvWithoutSplitDbArchitecture(): array
    {
        return [
            ['version' => 'master'],
        ];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProviderEnvWithSplitDbArchitecture
     * @throws Exception
     */
    public function testEnvWithSplitDbArchitecture(CliTester $I, Example $data)
    {
        try {
            if (self::$beforeShouldRun) {
                $this->prepareWorkplace($I, $data['version']);
                $services = $I->readServicesYaml();
                $magentoApp = $I->readAppMagentoYaml();
                $services['mysql-quote']['type'] = 'mysql:10.2';
                $services['mysql-sales']['type'] = 'mysql:10.2';
                $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
                $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
                $I->writeServicesYaml($services);
                $I->writeAppMagentoYaml($magentoApp);
                $I->runEceDockerCommand('build:compose --mode=production');
                $I->runDockerComposeCommand('run build cloud-build');
                self::$beforeShouldRun = false;
            }

            self::$counter++;

            $envMagentoYamlData = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];
            foreach ($data['types'] as $type) {
                $envMagentoYamlData['stage']['deploy']['SPLIT_DB'][] = $type;
            }
            $I->writeEnvMagentoYaml($envMagentoYamlData);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command wizard:split-db-state'));
            $I->seeInOutput($data['messages']);
            $I->stopEnvironment(true);
        } catch (Exception $exception) {
            self::$beforeShouldRun = true;
            self::$counter = 0;
            parent::_after($I);
            throw $exception;
        }
        if (self::$counter === $this->dataProviderEnvWithSplitDbArchitecture()) {
            self::$beforeShouldRun = true;
            self::$counter = 0;
            parent::_after($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderEnvWithSplitDbArchitecture(): array
    {
        return [
            [
                'types' => [],
                'messages' => [
                    'DB is not split',
                    '- You may split DB using SPLIT_DB variable in .magento.env.yaml file'
                ],
                'version' => 'master',
            ],
            [
                'types' => ['quote'],
                'messages' => ['DB is already split with type(s): quote',],
                'version' => 'master',
            ],
            [
                'types' => ['quote', 'sales'],
                'messages' => ['DB is already split with type(s): quote, sales'],
                'version' => 'master',
            ]
        ];
    }
}
