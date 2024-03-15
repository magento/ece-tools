<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 *
 * @group php83
 */
class ScdMatrixCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider scdOnDeployDataProvider
     */
    public function testScdOnDeploy(\CliTester $I, \Codeception\Example $data): void
    {
        $I->copyFileToWorkDir($data['env_yaml'], 'magento.env.yaml');
        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    protected function scdOnDeployDataProvider(): array
    {
        return [
            ['env_yaml' => 'files/scd/env_matrix_1.yaml'],
            ['env_yaml' => 'files/scd/env_matrix_2.yaml'],
            ['env_yaml' => 'files/scd/env_matrix_3.yaml'],
        ];
    }
}
