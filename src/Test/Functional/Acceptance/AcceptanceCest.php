<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Robo\Exception\TaskException;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
 *
 */
abstract class AcceptanceCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7';

    /**
     * @param CliTester $I
     *
     * @throws TaskException
     */
    public function _before(CliTester $I): void
    {
        parent::_before($I);

        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
    }

    /**
     * @param CliTester $I
     * @param Example $data
     *
     * @throws TaskException
     *
     * @dataProvider defaultDataProvider
     */
    public function testDefault(\CliTester $I, \Codeception\Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;
        $this->checkArraySubset($data['expectedConfig'], $config, $I);

        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command config:dump'));
        $destination = sys_get_temp_dir() . '/app/etc/config.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/config.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;
        $flattenKeysConfig = implode(array_keys($this->getArrayManager()->flatten($config, '#')));

        $I->assertStringContainsString('#modules', $flattenKeysConfig);
        $I->assertStringContainsString('#scopes', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/general/locale/code', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/dev/static/sign', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/dev/front_end_development_workflow', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/dev/template', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/dev/js', $flattenKeysConfig);
        $I->assertStringContainsString('#system/default/dev/css', $flattenKeysConfig);
        $I->assertStringContainsString('#system/stores', $flattenKeysConfig);
        $I->assertStringContainsString('#system/websites', $flattenKeysConfig);
        $I->assertStringContainsString('#admin_user/locale/code', $flattenKeysConfig);

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString('--admin-password=\'******\'', $log);
        if (strpos($log, '--db-password') !== false) {
            $I->assertStringContainsString('--db-password=\'******\'', $log);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    abstract protected function defaultDataProvider(): array;

    /**
     * @param CliTester $I
     * @throws TaskException
     */
    public function testWithOldNonSplitBuildCommand(\CliTester $I): void
    {
        $config = $I->readAppMagentoYaml();
        $config['hooks']['build'] = 'set -e' . PHP_EOL . 'php ./vendor/bin/ece-tools build' . PHP_EOL;
        $I->writeAppMagentoYaml($config);

        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param CliTester $I
     *
     * @throws TaskException
     */
    public function testDeployInBuild(\CliTester $I): void
    {
        $tmpConfig = sys_get_temp_dir() . '/app/etc/config.php';
        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $I->runDockerComposeCommand('run deploy ece-command config:dump');
        $I->assertStringNotContainsString(
            'Static content deployment was performed during the build phase or disabled. '
            . 'Skipping deploy phase static content compression.',
            $I->grabFileContent('/var/log/cloud.log')
        );
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $I->assertTrue(
            $I->downloadFromContainer('/app/etc/config.php', $tmpConfig, Docker::DEPLOY_CONTAINER),
            'Cannot download config.php from Docker'
        );

        $I->assertTrue($I->stopEnvironment());
        $I->assertTrue($I->copyFileToWorkDir($tmpConfig, 'app/etc/config.php'));
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->assertStringContainsString(
            'Static content deployment was performed during the build phase or disabled. '
            . 'Skipping deploy phase static content compression.',
            $I->grabFileContent('/var/log/cloud.log')
        );
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
