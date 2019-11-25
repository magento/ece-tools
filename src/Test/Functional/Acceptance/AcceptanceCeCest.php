<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;
use Robo\Exception\TaskException;
use CliTester;

/**
 * @inheritDoc
 *
 * @group edition-ce
 */
class AcceptanceCeCest extends AbstractInstallCest
{
    public const EDITION = 'CE';

    /**
     * @param CliTester $I
     *
     * @throws TaskException
     */
    public function testWithSplitBuildCommand(\CliTester $I): void
    {
        $I->assertTrue($I->runEceToolsCommand('build:generate', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('build:transfer', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
