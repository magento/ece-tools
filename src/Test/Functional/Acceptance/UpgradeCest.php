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
class UpgradeCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider testProvider
     */
    public function test(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->cloneTemplate($data['from']));
        $I->assertTrue($I->composerInstall());
        $this->assert($I);
        $I->runBinMagentoCommand('config:set general/region/state_required US --lock-env', Docker::DEPLOY_CONTAINER);
        $this->checkConfigurationIsNotRemoved($I);
        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/app/etc/di.xml', '/setup/*']));
        $I->assertTrue($I->composerRequireMagentoCloud($data['to']));
        $this->assert($I);
        $this->checkConfigurationIsNotRemoved($I);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    protected function assert(\CliTester $I)
    {
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));

        $I->amOnPage('/');
        $I->see('Home page');
    }

    /**
     * @param \CliTester $I
     * @return array
     */
    protected function checkConfigurationIsNotRemoved(\CliTester $I)
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;

        $I->assertArraySubset(
            ['general' => ['region' => ['state_required' => 'US']]],
            $config['system']['default']
        );
    }

    /**
     * @return array
     */
    protected function testProvider()
    {
        return [
            ['from' => '2.3.0', 'to' => '2.3.*']
        ];
    }
}
