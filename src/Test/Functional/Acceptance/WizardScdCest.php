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
 * @group php74
 */
class WizardScdCest extends AbstractCest
{
    public function _before(\CliTester $I): void
    {
        parent::_before($I);

        $I->runEceDockerCommand('build:compose --mode=production');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDefault(\CliTester $I): void
    {
        $I->runDockerComposeCommand('run build cloud-build');
        $I->assertFalse($I->runDockerComposeCommand('run build ece-command wizard:scd-on-build'));
        $I->seeInOutput(' - No stores/website/locales found in');
        $I->seeInOutput('SCD on build is disabled');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScdInBuildIsEnabled(\CliTester $I): void
    {
        $I->copyFileToWorkDir('files/scdinbuild/config.php', 'app/etc/config.php');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->assertTrue($I->runDockerComposeCommand('run build ece-command wizard:scd-on-build'));
        $I->seeInOutput('SCD on build is enabled');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScdOnDemandIsEnabled(\CliTester $I): void
    {
        $I->copyFileToWorkDir('files/scdondemand/.magento.env.yaml', '.magento.env.yaml');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->assertTrue($I->runDockerComposeCommand('run build ece-command wizard:scd-on-demand'));
        $I->seeInOutput('SCD on demand is enabled');
    }
}
