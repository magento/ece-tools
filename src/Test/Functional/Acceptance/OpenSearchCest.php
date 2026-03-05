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
 */
abstract class OpenSearchCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @throws       \Robo\Exception\TaskException
     * @dataProvider dataProvider
     */
    public function testOpen(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['magento']);

        $I->generateDockerCompose('--mode=production');
        $this->removeVendorVolumeMountFromDockerCompose($I);

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
    }

    /**
     * @param  \CliTester $I
     * @return array
     */
    private function getConfig(\CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        return include $destination;
    }

    /**
     * @param  \CliTester $I
     * @return void
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
    protected function dataProvider(): array
    {
        return [
            [
                'magento'        => $this->magentoCloudTemplate,
                'expectedResult' => [
                    'engine'                     => 'opensearch',
                    'opensearch_server_hostname' => 'opensearch',
                    'opensearch_server_port'     => '9200'
                ],
            ],
        ];
    }
}
