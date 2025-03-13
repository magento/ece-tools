<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;
use Robo\Exception\TaskException;
use CliTester;

/**
 * @inheritDoc
 *
 * @group php82
 * @group edition-ce
 */
class AcceptanceCeCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.6';

    /**
     * @var boolean
     */
    protected $runComposerUpdate = false;

    public function _before(\CliTester $I): void
    {
        parent::_before($I);

        $I->removeDependencyFromComposer('magento/magento-cloud-metapackage');
        $I->addDependencyToComposer(
            'magento/product-community-edition',
            $this->magentoCloudTemplate === 'master' ? '@stable' : $this->magentoCloudTemplate
        );
        $I->composerUpdate();
    }

    /**
     * @param CliTester $I
     *
     * @throws TaskException
     * @skip Magento CE does not work on the Cloud and MCD
     */
    public function testWithSplitBuildCommand(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
