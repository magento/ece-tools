<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Tests extensibility base deployment scenarios
 *
 * @group php83
 */
class ScenarioExtensibilityCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        parent::_before($I);

        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
        $I->createArtifact('ece-tools-extend', 'packages/ece-tools-extend');
        $I->addDependencyToComposer('magento/ece-tools-extend', '*');
        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScenarioExtensibilityAndPriority(\CliTester $I): void
    {
        $app = $I->readAppMagentoYaml();
        $app['hooks']['build'] = 'set -e' . PHP_EOL
            . 'php ./vendor/bin/ece-tools run ./vendor/magento/ece-tools/scenario/build/generate.xml'
            . ' ./vendor/magento/ece-tools-extend/scenario/extend-build-generate.xml'
            . ' ./vendor/magento/ece-tools-extend/scenario/extend-build-generate-skip-di.xml' . PHP_EOL
            . 'php ./vendor/bin/ece-tools run ./vendor/magento/ece-tools/scenario/build/transfer.xml'
            . ' ./vendor/magento/ece-tools-extend/scenario/extend-build-transfer.xml';
        $I->writeAppMagentoYaml($app);

        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();

        $cloudLog = $I->grabFileContent('/init/var/log/cloud.log', Docker::BUILD_CONTAINER);

        $I->assertStringContainsString(
            'Step "copy-sample-data" was skipped',
            $cloudLog,
            'Checks that step copy-sample-data was skipped'
        );
        $I->assertStringContainsString(
            'Step "compile-di" was skipped',
            $cloudLog,
            'Checks that step compile-di was skipped'
        );
        $I->assertStringContainsString(
            'Doing some actions after static content generation',
            $cloudLog,
            'Checks that new step update-static-content was added'
        );
        $I->assertStringContainsString(
            'Customized step for enabling production mode',
            $cloudLog,
            'Checks that step set-production-mode was customized'
        );

        $cloudLog = str_replace(PHP_EOL, " ", $cloudLog);
        $I->assertRegExp(
            '/(deploy-static-content).*?(update-static-content)/i',
            $cloudLog,
            'Checks that update-static-content step was run after deploy-static-content'
        );
        $I->assertRegExp(
            '/(clear-init-directory).*?(compress-static-content)/i',
            $cloudLog,
            'Checks that priority for step clear-init-directory and compress-static-content was swapped'
        );
        $I->assertNotRegExp(
            '/(compress-static-content).*?(clear-init-directory)/i',
            $cloudLog,
            'Checks that priority for step clear-init-directory and compress-static-content is different then in '
            . ' default scenario'
        );
    }
}
