<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 */
class ElasticSearchCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I)
    {
        // Do nothing
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider elasticDataProvider
     */
    public function testElastic(\CliTester $I, \Codeception\Example $data)
    {
        $I->generateDockerCompose($data['services']);
        $I->cleanUpEnvironment();
        $I->cloneTemplate($data['magento']);
        $I->addEceComposerRepo();
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));

        $I->runBinMagentoCommand('config:set general/region/state_required US --lock-env', Docker::DEPLOY_CONTAINER);
        $this->checkConfigurationIsNotRemoved($I);

        $I->amOnPage('/');
        $I->see('Home page');

        $config = $this->getConfig($I);
        $I->assertArraySubset(
            $data['expectedResult'],
            $config['system']['default']['catalog']['search']
        );

        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/setup/*']));

        $relationships = [
            'MAGENTO_CLOUD_RELATIONSHIPS' => [
                'database' => [
                    $I->getDbCredential(),
                ],
            ],
        ];

        $I->composerInstall();
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER, $relationships));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER, $relationships));
        $this->checkConfigurationIsNotRemoved($I);

        $I->amOnPage('/');
        $I->see('Home page');

        $config = $this->getConfig($I);
        $I->assertArraySubset(
            ['engine' => 'mysql'],
            $config['system']['default']['catalog']['search']
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
    private function checkConfigurationIsNotRemoved(\CliTester $I)
    {
        $config = $this->getConfig($I);

        $I->assertArraySubset(
            ['general' => ['region' => ['state_required' => 'US']]],
            $config['system']['default']
        );
    }

    /**
     * @return array
     */
    protected function elasticDataProvider(): array
    {
        return [
            [
                'magento' => '2.3.0',
                'services' => [],
                'expectedResult' => ['engine' => 'mysql'],
            ],
            [
                'magento' => '2.3.0',
                'services' => ['es' => '5.2'],
                'expectedResult' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'elasticsearch',
                    'elasticsearch5_server_port' => '9200'
                ],
            ],
            [
                'magento' => '2.3.1',
                'services' => ['es' => '6.5'],
                'expectedResult' => [
                    'engine' => 'elasticsearch6',
                    'elasticsearch6_server_hostname' => 'elasticsearch',
                    'elasticsearch6_server_port' => '9200'
                ],
            ],
        ];
    }
}
