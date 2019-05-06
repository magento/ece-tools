<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * @group php72
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
 */
class AcceptanceCest
{
    public function test(\CliTester $I)
    {
        $I->assertTrue($I->cloneTemplate());
        $I->assertTrue($I->composerInstall());
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));

        $I->amOnPage('/');
        $I->see('Home page');

        $I->assertTrue($I->runEceToolsCommand('config:dump', Docker::DEPLOY_CONTAINER));
        $destination = sys_get_temp_dir() . '/app/etc/config.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/config.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;

        $arrayManager = new ArrayManager();
        $flattenKeysConfig = implode(array_keys($arrayManager->flatten($config, '#')));

        $I->assertContains('#modules', $flattenKeysConfig);
        $I->assertContains('#scopes', $flattenKeysConfig);
        $I->assertContains('#system/default/general/locale/code', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/static/sign', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/front_end_development_workflow', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/template', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/js', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/css', $flattenKeysConfig);
        $I->assertContains('#system/stores', $flattenKeysConfig);
        $I->assertContains('#system/websites', $flattenKeysConfig);
        $I->assertContains('#admin_user/locale/code', $flattenKeysConfig);

        $I->amOnPage('/');
        $I->see('Home page');
    }
}
