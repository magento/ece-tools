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
        $I->startEnvironment();
        $I->assertTrue($I->cloneTemplate($data['from']));
        $I->assertTrue($I->addEceComposerRepo());
        $this->assert($I);
        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/setup/*']));
        $I->assertTrue($I->composerRequireMagentoCloud($data['to']));
        $this->assert($I);
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
     * @return array
     */
    protected function testProvider()
    {
        // @TODO change version to 2.3.* after fix in magento core.
        // https://magento2.atlassian.net/browse/MAGECLOUD-3725
        return [
            ['from' => '2.3.0', 'to' => '2.3.1']
        ];
    }
}
