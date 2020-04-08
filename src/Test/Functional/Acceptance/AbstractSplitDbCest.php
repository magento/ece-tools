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
 * Checks Split Database Functionality
 */
abstract class AbstractSplitDbCest extends AbstractCest
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
     */
    private function internalAfter(CliTester $I): void
    {
        self::$beforeShouldRun = true;
        self::$counter = 0;
        parent::_after($I);
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider dataProviderVersion
     */
    public function testDeploySplitDbInEnvWithoutSplitDbArchitecture(CliTester $I, Example $data)
    {
        try {
            $this->prepareWorkplace($I, $data['version']);
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => ['quote', 'sales']],
                ]
            ]);
            $I->runEceDockerCommand('build:compose --mode=production');
            $I->startEnvironment();
            $I->runDockerComposeCommand('run build cloud-build');
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput(
                'ERROR: Enabling a split database will be skipped.'
                . ' Relationship do not have configuration for next types: sales, quote'
            );
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayNotHasKey('checkout', $config['db']['connection']);
            $I->assertArrayNotHasKey('sales', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);
            $I->assertArrayNotHasKey('checkout', $config['resource']);
            $I->assertArrayNotHasKey('sales', $config['resource']);
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
        } finally {
            parent::_after($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderVersion(): array
    {
        return [
            ['version' => 'master']
        ];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderDeploySplitDbWithInvalidSplitTypes
     */
    public function testDeploySplitDbWithInvalidSplitTypes(CliTester $I, Example $data)
    {
        try {
            if (self::$beforeShouldRun) {
                $this->prepareWorkplace($I, $data['version']);
                self::$beforeShouldRun = false;
            }

            self::$counter++;
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => $data['types']]
                ]
            ]);
            $I->runEceDockerCommand('build:compose --mode=production');
            $I->runDockerComposeCommand('run build cloud-build');
            $I->seeInOutput($data['messages']);
        } catch (Exception $exception) {
            $this->internalAfter($I);
            throw $exception;
        }
        if (self::$counter === $this->dataProviderDeploySplitDbWithInvalidSplitTypes()) {
            $this->internalAfter($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderDeploySplitDbWithInvalidSplitTypes(): array
    {
        return [
            [
                'types' => 'quote',
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains an invalid value of type string. Use the following type: array.',
                ],
                'version' => 'master',
            ],
            [
                'types' => ['checkout'],
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.',
                    'It should be array with next available values: [quote, sales].'
                ],
                'version' => 'master',
            ],
            [
                'types' => ['quote', 'checkout'],
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.'
                    , 'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.'
                    , 'It should be array with next available values: [quote, sales].',
                ],
                'version' => 'master'
            ],
        ];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider dataProviderVersion
     */
    public function testDeploySplitDbWithRemovingExistsSplitDbType(CliTester $I, Example $data)
    {
        try {
            $this->prepareConfigToDeploySplitDb($I, $data['version']);
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => ['quote']],
                ]
            ]);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run build cloud-build');
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput([
                'INFO: Quote tables were split to DB magento2 in db-quote',
                'INFO: Running setup upgrade.',
            ]);
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayHasKey('checkout', $config['db']['connection']);
            $I->assertArrayNotHasKey('sales', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);
            $I->assertArrayHasKey('checkout', $config['resource']);
            $I->assertArrayNotHasKey('sales', $config['resource']);
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
            $I->stopEnvironment(true);
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => []]
                ]
            ]);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput('WARNING: Variable SPLIT_DB does not have data which were already split types: quote');
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayHasKey('checkout', $config['db']['connection']);
            $I->assertArrayNotHasKey('sales', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);
            $I->assertArrayHasKey('checkout', $config['resource']);
            $I->assertArrayNotHasKey('sales', $config['resource']);
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
            $I->stopEnvironment(true);
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => ['sales']]
                ]
            ]);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput('WARNING: Variable SPLIT_DB does not have data which were already split types: quote');
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayHasKey('checkout', $config['db']['connection']);
            $I->assertArrayNotHasKey('sales', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);
            $I->assertArrayHasKey('checkout', $config['resource']);
            $I->assertArrayNotHasKey('sales', $config['resource']);
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
            $I->stopEnvironment(true);
            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => ['quote', 'sales']]
                ]
            ]);
            $I->startEnvironment();
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput([
                'INFO: Sales tables were split to DB magento2 in db-sales',
                'INFO: Running setup upgrade.',
            ]);
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayHasKey('checkout', $config['db']['connection']);
            $I->assertArrayHasKey('sales', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);
            $I->assertArrayHasKey('checkout', $config['resource']);
            $I->assertArrayHasKey('sales', $config['resource']);
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
        } finally {
            parent::_after($I);
        }
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderTestDeploySplitDb
     */
    public function testSplitDbDeploy(CliTester $I, Example $data): void
    {
        try {
            if (self::$beforeShouldRun) {
                $this->prepareConfigToDeploySplitDb($I, $data['version']);
                self::$beforeShouldRun = false;
            }

            self::$counter++;

            $I->writeEnvMagentoYaml([
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true],
                    'deploy' => ['SPLIT_DB' => $data['types']]
                ]
            ]);

            $I->startEnvironment();
            $I->runDockerComposeCommand('run build cloud-build');
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->seeInOutput($data['messages']);
            $destination = sys_get_temp_dir() . '/app/etc/env.php';
            $I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER);
            $config = require $destination;
            $I->assertArrayHasKey('default', $config['db']['connection']);
            $I->assertArrayHasKey('indexer', $config['db']['connection']);
            $I->assertArrayHasKey('default_setup', $config['resource']);

            foreach ($data['connection'] as $connection) {
                $I->assertArrayHasKey($connection, $config['db']['connection']);
                $I->assertArrayHasKey($connection, $config['resource']);
            }

            foreach ($data['types'] as $type) {
                $I->amConnectedToDatabase('db_' . $type);
                foreach ($this->getListTablesBySplitDbType($type) as $table) {
                    $I->grabNumRecords($table);
                }
            }
            $I->runDockerComposeCommand('run deploy cloud-post-deploy');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
            $I->stopEnvironment();
        } catch (Exception $exception) {
            $this->internalAfter($I);
            throw $exception;
        }
        if (self::$counter === $this->dataProviderDeploySplitDbWithInvalidSplitTypes()) {
            $this->internalAfter($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderTestDeploySplitDb(): array
    {
        return [
            [
                'connection' => ['checkout'],
                'types' => ['quote'],
                'messages' => [
                    'INFO: Quote tables were split to DB magento2 in db-quote',
                    'INFO: Running setup upgrade.',
                ],
                'version' => 'master',
            ],
            [
                'connection' => ['sales'],
                'types' => ['sales'],
                'messages' => [
                    'INFO: Sales tables were split to DB magento2 in db-sales',
                    'INFO: Running setup upgrade.',
                ],
                'version' => 'master',
            ],
            [
                'connection' => ['checkout', 'sales'],
                'types' => ['quote', 'sales'],
                'messages' => [
                    'INFO: Quote tables were split to DB magento2 in db-quote',
                    'INFO: Running setup upgrade.',
                    'INFO: Sales tables were split to DB magento2 in db-sales',
                    'INFO: Running setup upgrade.',
                ],
                'version' => 'master',
            ],
        ];
    }

    /**
     * @param string $type
     * @return array
     */
    private function getListTablesBySplitDbType(string $type): array
    {
        switch ($type) {
            case 'quote':
                return [
                    'quote_id_mask',
                    'quote_address_item',
                    'quote_address',
                    'quote',
                ];
            case 'sales':
                return [
                    'sales_invoice',
                    'sales_invoice_grid',
                    'sales_invoice_item',
                    'sales_order',
                    'sales_order_grid',
                    'sales_order_tax',
                ];
        }
        return [];
    }

    /**
     * @param CliTester $I
     * @param string $version
     * @throws TaskException
     */
    private function prepareConfigToDeploySplitDb(CliTester $I, string $version)
    {
        $this->prepareWorkplace($I, $version);
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --expose-db-port=%s'
            . ' --expose-db-quote-port=%s --expose-db-sales-port=%s',
            $I->getExposedPort(),
            $I->getExposedPort('db_quote'),
            $I->getExposedPort('db_sales')
        ));
    }
}
