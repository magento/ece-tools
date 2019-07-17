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
class PatchApplierCest extends AbstractCest
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
        $I->uploadToContainer('files/debug_logging/.magento.env.yaml', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testApplyingPatch(\CliTester $I)
    {
        $I->uploadToContainer('files/patches/target_file.md', '/target_file.md', Docker::BUILD_CONTAINER);
        $I->uploadToContainer('files/patches/patch.patch', '/m2-hotfixes/patch.patch', Docker::BUILD_CONTAINER);

        // For this test, only the build phase is enough
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertContains('# Hello Magento', $targetFile);
        $I->assertContains('## Additional Info', $targetFile);
        $log = $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER);
        $I->assertContains('INFO: Applying patch /var/www/magento/m2-hotfixes/patch.patch', $log);
        $I->assertContains('DEBUG: git apply /var/www/magento/m2-hotfixes/patch.patch', $log);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testApplyingExistingPatch(\CliTester $I)
    {
        $I->uploadToContainer('files/patches/target_file_applied_patch.md', '/target_file.md', Docker::BUILD_CONTAINER);
        $I->uploadToContainer('files/patches/patch.patch', '/m2-hotfixes/patch.patch', Docker::BUILD_CONTAINER);

        // For this test, only the build phase is enough
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertContains('# Hello Magento', $targetFile);
        $I->assertContains('## Additional Info', $targetFile);
        $I->assertContains(
            'Patch /var/www/magento/m2-hotfixes/patch.patch was already applied',
            $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER)
        );
    }
}
