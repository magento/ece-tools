<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;

/**
 * This test runs on the latest version of PHP
 *
 * @group php83
 */
class ErrorMessageCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testShellErrorMessage(CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->cleanDirectories(['/bin/*']);
        $I->assertFalse($I->runDockerComposeCommand('run build ece-command build'));
        $I->seeInOutput('Could not open input file: ./bin/magento');
    }
}
