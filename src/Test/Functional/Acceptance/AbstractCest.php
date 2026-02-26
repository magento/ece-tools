<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Util\ArrayManager;
use Symfony\Component\Yaml\Yaml;

/**
 * General Cest
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractCest
{
    /**
     * @var boolean
     */
    protected bool $removeEs = true;

    /**
     * @var boolean
     */
    protected bool $runComposerUpdate = true;

    /**
     * @var string
     */
    protected string $magentoCloudTemplate = 'master';

    /**
     * @var ArrayManager|null
     */
    protected ?ArrayManager $arrayManager = null;

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
     * @param  array $data
     * @return string
     */
    protected function convertEnvFromArrayToJson(array $data): string
    {
        return addslashes(json_encode($data));
    }

    /**
     * @param \CliTester $I
     * @param string     $templateVersion
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
        $I->createArtifactCurrentTestedCode('ece-tools', '2002.2.99');
        $I->addArtifactsRepoToComposer();
        $I->addDependencyToComposer('magento/ece-tools', '2002.2.99');
        $I->addEceDockerGitRepoToComposer();
        $I->addCloudComponentsGitRepoToComposer();
        $I->addCloudPatchesGitRepoToComposer();
        $I->addQualityPatchesGitRepoToComposer();

        $dependencies = [
            'magento/magento-cloud-docker',
            'magento/magento-cloud-components',
            'magento/magento-cloud-patches',
            'magento/quality-patches'
        ];

        foreach ($dependencies as $dependency) {
            $I->assertTrue(
                $I->addDependencyToComposer($dependency, $I->getDependencyVersion($dependency)),
                'Can not add dependency ' . $dependency
            );
        }

        if ($this->runComposerUpdate) {
            $I->assertTrue($I->composerUpdate(), 'Composer update failed');
            $I->cacheWorkDir($templateVersion);
        }

        $this->removeESIfExists($I, $templateVersion);
    }

    /**
     * Removes the dedicated vendor volume mount from generated docker-compose.yml.
     *
     * We want `/app/vendor` to be a normal directory inside the `/app` root mount, otherwise
     * Composer may fail trying to delete `/app/vendor` (mounted volume root).
     */
    protected function removeVendorVolumeMountFromDockerCompose(\CliTester $I): void
    {
        $composePath = $I->getWorkDirPath() . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        if (!is_file($composePath)) {
            return;
        }

        $compose = Yaml::parseFile($composePath);
        if (!is_array($compose) || !isset($compose['services']) || !is_array($compose['services'])) {
            return;
        }

        $vendorVolumeNames = [];

        foreach ($compose['services'] as &$service) {
            if (!is_array($service) || !isset($service['volumes']) || !is_array($service['volumes'])) {
                continue;
            }

            $service['volumes'] = array_values(array_filter(
                $service['volumes'],
                static function ($volume) use (&$vendorVolumeNames): bool {
                    if (!is_string($volume)) {
                        return true;
                    }

                    if (preg_match('#^([^:]+):/app/vendor(?::|$)#', $volume, $matches)) {
                        $vendorVolumeNames[] = $matches[1];
                        return false;
                    }

                    return true;
                }
            ));
        }
        unset($service);

        if (isset($compose['volumes']) && is_array($compose['volumes'])) {
            foreach (array_unique($vendorVolumeNames) as $name) {
                unset($compose['volumes'][$name]);
            }
        }

        file_put_contents($composePath, Yaml::dump($compose, 25, 2));
    }

    /**
     * Checks if we can remove ES configuration for tests.
     *
     * @param  string $templateVersion
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
     * @param string     $templateVersion
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

    /**
     * @return ArrayManager
     */
    protected function getArrayManager(): ArrayManager
    {
        if ($this->arrayManager == null) {
            $this->arrayManager = new ArrayManager();
        }

        return $this->arrayManager;
    }

    /**
     * Perform asserts for arrays to check that $array contains information from $subset
     *
     * @param  array      $subset
     * @param  array      $array
     * @param  \CliTester $I
     * @return void
     */
    protected function checkArraySubset(array $subset, array $array, \CliTester $I): void
    {
        $flattenArray = $this->getArrayManager()->flatten($array);
        $flattenSubset = $this->getArrayManager()->flatten($subset);
        foreach ($flattenSubset as $path => $value) {
            $I->assertArrayHasKey($path, $flattenArray);
            $I->assertEquals($value, $flattenArray[$path]);
        }
    }
}
