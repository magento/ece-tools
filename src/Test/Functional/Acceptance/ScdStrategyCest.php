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
 */
abstract class ScdStrategyCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider scdStrategyDataProvider
     */
    public function testScdStrategyOnDeploy(\CliTester $I, \Codeception\Example $data): void
    {
        $I->copyFileToWorkDir($data['env_yaml'], '.magento.env.yaml');
        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString('-s ' . $data['strategy'], $log);
    }

    /**
     * @return array
     */
    abstract protected function scdStrategyDataProvider(): array;
}
