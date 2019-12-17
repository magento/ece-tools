<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 */
class ErrorMessageCest extends AbstractCest
{
    /**
     * @param CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(CliTester $I)
    {
        parent::_before($I);
        $I->cloneTemplate();
        $I->addEceComposerRepo();
    }

    /**
     * @param CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testShellErrorMessage(CliTester $I)
    {
        $I->cleanDirectories(['/bin/*']);
        $I->assertFalse($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->seeInOutput('Could not open input file: ./bin/magento');
    }
}
