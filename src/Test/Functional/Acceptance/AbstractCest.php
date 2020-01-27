<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * General Cest
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        $I->cleanupWorkDir();
        $I->cloneTemplateToWorkDir('2.3.2');
        $I->createAuthJson();
        $I->createArtifactsDir();
        $I->createArtifactCurrentTestedCode('ece-tools');
        $I->addArtifactsRepoToComposer();
        $I->addDependencyToComposer('magento/ece-tools', '2002.0.99');
        $I->addEceDockerGitRepoToComposer();
        $I->addDependencyToComposer(
            'magento/magento-cloud-docker',
            $I->getDependencyVersion('magento/magento-cloud-docker')
        );
        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        //$I->resetFilesOwner();
        //$I->stopEnvironment();
        //$I->removeWorkDir();
    }
}
