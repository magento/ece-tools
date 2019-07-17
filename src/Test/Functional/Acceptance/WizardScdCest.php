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
class WizardScdCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I)
    {
        parent::_before($I);
        $I->assertTrue($I->cloneTemplate());
        $I->assertTrue($I->addEceComposerRepo());
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDefault(\CliTester $I)
    {
        $I->assertFalse($I->runEceToolsCommand('wizard:scd-on-build', Docker::BUILD_CONTAINER));
        $I->seeInOutput(' - No stores/website/locales found in');
        $I->seeInOutput('SCD on build is disabled');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScdInBuildIsEnabled(\CliTester $I)
    {
        $I->assertTrue($I->uploadToContainer(
            'files/scdinbuild/config.php',
            '/app/etc/config.php',
            Docker::BUILD_CONTAINER
        ));
        $I->assertTrue($I->runEceToolsCommand('wizard:scd-on-build', Docker::BUILD_CONTAINER));
        $I->seeInOutput('SCD on build is enabled');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScdOnDemandIsEnabled(\CliTester $I)
    {
        $I->uploadToContainer('files/scdondemand/.magento.env.yaml', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
        $I->assertTrue($I->runEceToolsCommand('wizard:scd-on-demand', Docker::BUILD_CONTAINER));
        $I->seeInOutput('SCD on demand is enabled');
    }
}
