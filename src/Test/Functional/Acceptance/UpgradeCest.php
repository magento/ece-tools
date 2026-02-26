<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Abstract upgrade test for Magento Cloud Docker environments.
 *
 * This base class contains reusable upgrade logic across multiple PHP version groups.
 */
abstract class UpgradeCest extends AbstractCest
{
    /**
     * Do nothing before each test (override if needed).
     *
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        // No setup required here...
    }

    /**
     * Executes the full Magento upgrade workflow:
     * - Deploy old version
     * - Upgrade to new version
     * - Redeploy and validate
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider testProvider
     */
    public function testUpgrade(\CliTester $I, \Codeception\Example $data): void
    {
        // Step 1: Deploy old version
        $this->prepareWorkplace($I, $data['from']);
        $I->generateDockerCompose('--mode=production');
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $I->runDockerComposeCommand('run build cloud-build');
        $I->assertTrue($I->startEnvironment(), 'Environment failed to start');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post-deploy failed');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        // Step 2: Stop env and upgrade
        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/setup/*']), 'Failed to clean directories');
        $I->stopEnvironment(true);
        $I->addDependencyToComposer('magento/magento-cloud-metapackage', $data['to']);
        $I->composerUpdate();

        // Step 3: Redeploy upgraded version
        $this->assertUpgradeSuccess($I);
    }

    /**
     * Redeploys and asserts upgraded Magento works correctly.
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    protected function assertUpgradeSuccess(\CliTester $I): void
    {
        #$I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Upgrade build failed');
        $I->runDockerComposeCommand('run build cloud-build');
        
        $I->assertTrue($I->startEnvironment(), 'Failed to start upgraded environment');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Upgrade deploy failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Upgrade post-deploy failed');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Provides version combinations to test upgrade from → to.
     *
     * Must be implemented in concrete subclasses.
     *
     * Example:
     *   return [
     *     ['from' => '2.4.7', 'to' => '>=2.4.8'],
     *   ];
     *
     * @return array
     */
    abstract protected function testProvider(): array;
}
