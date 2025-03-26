<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 *
 * @group php74
 */
class ElasticSearchCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider elasticDataProvider
     * @skip Skip Need to fix ElasticSearch containers
     */
    public function testElastic(\CliTester $I, \Codeception\Example $data): void
    {
        $this->removeEs = $data['removeES'];

        $this->prepareWorkplace($I, $data['magento']);

        $I->generateDockerCompose('--mode=production');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $I->runDockerComposeCommand(
            'run deploy magento-command config:set general/region/state_required US --lock-env'
        );
        $this->checkConfigurationIsNotRemoved($I);

        $I->amOnPage('/');
        $I->see('Home page');

        $config = $this->getConfig($I);
        $this->checkArraySubset(
            $data['expectedResult'],
            $config['system']['default']['catalog']['search'],
            $I
        );

        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/setup/*']));
        $I->stopEnvironment(true);
        $this->removeEs = true;
        $this->removeESIfExists($I);

        $I->generateDockerCompose('--mode=production');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $this->checkConfigurationIsNotRemoved($I);

        $I->amOnPage('/');
        $I->see('Home page');

        $config = $this->getConfig($I);
        $this->checkArraySubset(
            ['engine' => 'mysql'],
            $config['system']['default']['catalog']['search'],
            $I
        );
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
     * @return array
     */
    private function checkConfigurationIsNotRemoved(\CliTester $I): void
    {
        $config = $this->getConfig($I);
        $this->checkArraySubset(
            ['general' => ['region' => ['state_required' => 'US']]],
            $config['system']['default'],
            $I
        );
    }

    /**
     * @return array
     */
    protected function elasticDataProvider(): array
    {
        return [
            [
                'magento' => '2.4.3',
                'removeES' => false,
                'expectedResult' => [
                    'engine' => 'elasticsearch6',
                    'elasticsearch6_server_hostname' => 'elasticsearch',
                    'elasticsearch6_server_port' => '9200'
                ],
            ],
        ];
    }
}
