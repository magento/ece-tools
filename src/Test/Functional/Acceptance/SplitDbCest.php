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
use Exception;

/**
 * Checks split database functionality
 *
 * @group php83
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
        $I->writeEnvMagentoYaml(['stage' => ['global' => ['SCD_ON_DEMAND' => true]]]);

        // Deploy 'Split Db' in an environment without prepared architecture
        $I->generateDockerCompose('--mode=production');

        foreach ($this->variationsDataPartWithoutSplitDbArch() as $variationData) {
            $this->setSplitDbTypesIntoMagentoEnvYaml($I, $variationData['splitDbTypes']);
            $I->runDockerComposeCommand('run build cloud-build');
            $I->seeInOutput($variationData['messages']);
            $I->stopEnvironment();
        }

        // Deploy 'Split Db' with the unavailable Split Db types
        $this->setSplitDbTypesIntoMagentoEnvYaml($I, ['quote', 'sales']);
        $this->runDeploy($I);
        $I->seeInOutput(
            'Enabling a split database will be skipped.'
            . ' Relationship do not have configuration for next types: sales, quote'
        );
        $this->checkEnvPhpConfig($I, [], ['checkout', 'sales']);
        $this->checkMagentoFront($I);

        $I->stopEnvironment(true);

        // Prepare config for deploy Split Db
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);

        // Restore app/etc after build phase
        $I->runDockerComposeCommand('run build bash -c "cp -r /app/init/app/etc /app/app"');

        // Deploy 'Split Db' in an environment with prepared architecture. Case with upgrade
        $I->generateDockerCompose('--mode=production');
        foreach ($this->variationsDataPartWithSplitDbArch() as $variationData) {
            $this->setSplitDbTypesIntoMagentoEnvYaml($I, $variationData['splitDbTypes']);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput($variationData['messages']);
            $this->checkEnvPhpConfig($I, $variationData['expectedExists'], $variationData['expectedNotExist']);
            $this->checkMagentoFront($I);
            $I->stopEnvironment(true);
        }

        $I->stopEnvironment();

        // Install with Split db
        $this->setSplitDbTypesIntoMagentoEnvYaml($I, ['quote', 'sales']);
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
            ['version' => '2.4.7-beta-test'],
        ];
    }

    /**
     * @return array
     */
    private function variationsDataPartWithoutSplitDbArch(): array
    {
        return [
            'Deploy \'Split Db\' with the wrong Split Db type' => [
                'messages' => [
                    'Fix configuration with given suggestions:',
                    'Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains an invalid value of type string. Use the following type: array.',
                ],
                'splitDbTypes' => 'quote',
            ],
            'Deploy \'Split Db\' with the invalid Split Db label' => [
                'messages' => [
                    'Fix configuration with given suggestions:',
                    'Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.',
                    'It should be an array with following values: [quote, sales].'
                ],
                'splitDbTypes' => ['checkout'],
            ],
            'Deploy \'Split Db\' with the invalid and valid Split Db labels' => [
                'messages' => [
                    'Fix configuration with given suggestions:',
                    'Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.',
                    'It should be an array with following values: [quote, sales].',
                ],
                'splitDbTypes' => ['quote', 'checkout'],
            ]
        ];
    }

    /**
     * @return array
     */
    private function variationsDataPartWithSplitDbArch(): array
    {
        return [
            'Run splitting database for `quote` tables' => [
                'splitDbTypes' => ['quote'],
                'messages' => [
                    'INFO: Quote tables were split to DB magento2 in db-quote',
                    'INFO: Running setup upgrade.',
                ],
                'expectedExists' => ['checkout'],
                'expectedNotExist' => ['sales'],
            ],
            'Split Db type was deleted' => [
                'splitDbTypes' => null,
                'messages' => 'The SPLIT_DB variable is missing the configuration for split connection types: quote',
                'expectedExists' => ['checkout'],
                'expectedNotExist' => ['sales'],
            ],
            'Split Db  current type was deleted and new type added' => [
                'splitDbTypes' => ['sales'],
                'messages' => 'The SPLIT_DB variable is missing the configuration for split connection types: quote',
                'expectedExists' => ['checkout'],
                'expectedNotExist' => ['sales'],
            ],
            'Split Db current type was returned' => [
                'splitDbTypes' => ['sales', 'quote'],
                'messages' => [
                    'INFO: Sales tables were split to DB magento2 in db-sales',
                    'INFO: Running setup upgrade.',
                ],
                'expectedExists' => ['checkout', 'sales'],
                'expectedNotExist' => [],
            ]
        ];
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
}
