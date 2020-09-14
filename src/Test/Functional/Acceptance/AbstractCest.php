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
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @var boolean
     */
    protected $runComposerUpdate = true;

    /**
     * @var string
     */
    protected $magentoCloudTemplate = 'master';

    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        $this->prepareWorkplace($I, $this->magentoCloudTemplate);
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->stopEnvironment();
        $I->removeWorkDir();
    }

    /**
     * @param array $data
     * @return string
     */
    protected function convertEnvFromArrayToJson(array $data): string
    {
        return addslashes(json_encode($data));
    }

    /**
     * @param \CliTester $I
     * @param string $templateVersion
     */
    protected function prepareWorkplace(\CliTester $I, string $templateVersion): void
    {
        $I->cleanupWorkDir();

        if ($I->isCacheWorkDirExists($templateVersion)) {
            $I->restoreWorkDirFromCache($templateVersion);
            $this->removeESIfExists($I, $templateVersion);

            return;
        }

        $I->cloneTemplateToWorkDir($templateVersion);
        $I->createAuthJson();
        $I->createArtifactsDir();
        $I->createArtifactCurrentTestedCode('ece-tools', '2002.1.99');
        $I->addArtifactsRepoToComposer();
        $I->addDependencyToComposer('magento/ece-tools', '2002.1.99');
        $I->addEceDockerGitRepoToComposer();
        $I->addCloudComponentsGitRepoToComposer();
        $I->addCloudPatchesGitRepoToComposer();
        $I->addQualityPatchesGitRepoToComposer();
        $I->addDependencyToComposer(
            'magento/magento-cloud-docker',
            $I->getDependencyVersion('magento/magento-cloud-docker')
        );
        $I->addDependencyToComposer(
            'magento/magento-cloud-components',
            $I->getDependencyVersion('magento/magento-cloud-components')
        );
        $I->addDependencyToComposer(
            'magento/magento-cloud-patches',
            $I->getDependencyVersion('magento/magento-cloud-patches')
        );
        $I->addDependencyToComposer(
            'magento/quality-patches',
            $I->getDependencyVersion('magento/quality-patches')
        );

        if ($this->runComposerUpdate) {
            $I->composerUpdate();
            $I->cacheWorkDir($templateVersion);
        }

        $this->removeESIfExists($I, $templateVersion);
    }

    /**
     * Checks if we can remove ES configuration for tests.
     *
     * @param string $templateVersion
     * @return bool
     */
    protected function canESbeRemoved(string $templateVersion): bool
    {
        if ($templateVersion === 'master') {
            return false;
        }

        return (bool)version_compare($templateVersion, '2.4.0', '<');
    }

    /**
     * @param \CliTester $I
     * @param string $templateVersion
     */
    protected function removeESIfExists(\CliTester $I, string $templateVersion): void
    {
        if ($this->removeEs && $this->canESbeRemoved($templateVersion)) {
            $services = $I->readServicesYaml();

            if (isset($services['elasticsearch'])) {
                unset($services['elasticsearch']);
                $I->writeServicesYaml($services);

                $app = $I->readAppMagentoYaml();
                unset($app['relationships']['elasticsearch']);
                $I->writeAppMagentoYaml($app);
            }
        }
    }
}
