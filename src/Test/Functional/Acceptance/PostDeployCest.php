<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * @group php72
 */
class PostDeployCest
{
    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider postDeployDataProvider
     */
    public function testPostDeploy(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->cloneTemplate('2.3.1'));
        $I->assertTrue($I->composerInstall());
        $I->uploadToContainer('files/.magento.env.yaml.scdondemand', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER, $data['variables']));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER, $data['variables']));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER, $data['variables']));

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('NOTICE: Starting post-deploy.', $log);
        $I->assertContains('INFO: Warmed up page:', $log);
        $I->assertContains('NOTICE: Post-deploy is complete.', $log);
    }

    /**
     * @return array
     */
    public function postDeployDataProvider(): array
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
        $I->assertTrue($I->cloneTemplate('2.3.1'));
        $I->assertTrue($I->composerInstall());
        $I->uploadToContainer(
            'files/.magento.env.yaml.wrong_db_configuration',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertFalse($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('Fix configuration with given suggestions', $log);
        $I->assertContains('Post-deploy is skipped because deploy was failed.', $log);
        $I->assertNotContains('NOTICE: Starting post-deploy.', $log);
        $I->assertNotContains('INFO: Warmed up page:', $log);
        $I->assertNotContains('NOTICE: Post-deploy is complete.', $log);
    }
}
