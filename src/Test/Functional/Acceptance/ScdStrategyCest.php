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
class ScdStrategyCest extends AbstractCest
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
     * @dataProvider scdStrategyDataProvider
     */
    public function testScdStrategyOnDeploy(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->uploadToContainer($data['env_yaml'], '/.magento.env.yaml', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('-s ' . $data['strategy'], $log);
    }

    /**
     * @return array
     */
    protected function scdStrategyDataProvider(): array
    {
        return [
            [
              'env_yaml' => 'files/scd/scd-strategy-quick.yaml',
              'strategy' => 'quick'
            ],

            [
              'env_yaml' => 'files/scd/scd-strategy-standard.yaml',
              'strategy' => 'standard'
            ],
            [
              'env_yaml' => 'files/scd/scd-strategy-compact.yaml',
              'strategy' => 'compact'
            ],
        ];
    }
}
