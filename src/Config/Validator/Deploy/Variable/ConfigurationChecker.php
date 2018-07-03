<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy\Variable;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Checks that variable is configured for deploy phase in cloud admin panel
 * or in .magento.env.yaml in deploy(global if $checkGlobal is true) section.
 */
class ConfigurationChecker
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @param Environment $environment
     * @param EnvironmentReader $environmentReader
     */
    public function __construct(
        Environment $environment,
        EnvironmentReader $environmentReader
    ) {
        $this->environment = $environment;
        $this->environmentReader = $environmentReader;
    }

    /**
     * Checks that variable is configured in cloud admin panel
     * or in .magento.env.yaml in deploy(global if $checkGlobal is true) section.
     *
     * @param string $variableName
     * @param bool $checkGlobal
     * @return bool
     */
    public function isConfigured(string $variableName, bool $checkGlobal = false): bool
    {
        $envVariables = $this->environment->getVariables();

        if (isset($envVariables[$variableName])) {
            return true;
        }

        try {
            $stageConfig = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];

            if (isset($stageConfig[StageConfigInterface::STAGE_DEPLOY][$variableName])) {
                return true;
            }

            if ($checkGlobal && isset($stageConfig[StageConfigInterface::STAGE_GLOBAL][$variableName])) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
