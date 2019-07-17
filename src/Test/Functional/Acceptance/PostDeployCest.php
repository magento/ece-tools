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
class PostDeployCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I)
    {
        parent::_before($I);
        $I->cloneTemplate();
        $I->addEceComposerRepo();
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider postDeployDataProvider
     */
    public function testPostDeploy(\CliTester $I, \Codeception\Example $data)
    {
        $I->uploadToContainer('files/scdondemand/.magento.env.yaml', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER, $data['variables']));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER, $data['variables']));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER, $data['variables']));

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('NOTICE: Starting post-deploy.', $log);
        $I->assertContains('NOTICE: Post-deploy is complete.', $log);
    }

    /**
     * @return array
     */
    protected function postDeployDataProvider(): array
    {
        return [
            ['variables' => ['ADMIN_EMAIL' => 'admin@example.com']],
            ['variables' => []],
        ];
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testPostDeployIsNotRun(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/wrong_db_configuration/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertFalse($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->seeInOutput('Variable DATABASE_CONFIGURATION is not configured properly');
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('Fix configuration with given suggestions', $log);
        $I->assertContains('Post-deploy is skipped because deploy was failed.', $log);
        $I->assertNotContains('NOTICE: Starting post-deploy.', $log);
        $I->assertNotContains('INFO: Warmed up page:', $log);
        $I->assertNotContains('NOTICE: Post-deploy is complete.', $log);
    }
}
