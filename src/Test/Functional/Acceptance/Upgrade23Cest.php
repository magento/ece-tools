<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php72
 */
class Upgrade23Cest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        // Do nothing...
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider testProvider
     */
    public function test(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['from']);
        $I->generateDockerCompose('--mode=production');
        $this->assert($I);
        $I->assertTrue($I->cleanDirectories(['/vendor/*', '/setup/*']));
        $I->stopEnvironment(true);
        $I->addDependencyToComposer('magento/magento-cloud-metapackage', $data['to']);
        $I->composerUpdate();
        $this->assert($I);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    protected function assert(\CliTester $I): void
    {

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    protected function testProvider(): array
    {
        // @TODO change version to 2.3.* after fix in magento core.
        // https://magento2.atlassian.net/browse/MAGECLOUD-3725
        return [
            ['from' => '2.3.0', 'to' => '>=2.3.1 <2.3.2'],
            ['from' => '2.3.3', 'to' => '>=2.3.4 <2.3.5'],
        ];
    }
}
