<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Environment\ReaderInterface as EnvironmentReader;

/**
 * Checks that scd configuration is really using on build phase.
 *
 * For example, if SCD_STRATEGY is configured for build phase, but static won't generates on build phase, this validator
 * returns appropriate message.
 */
class ScdOptionsIgnorance implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var Validator\GlobalStage\ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param EnvironmentReader $environmentReader
     * @param Validator\GlobalStage\ScdOnBuild $scdOnBuild
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        EnvironmentReader $environmentReader,
        Validator\GlobalStage\ScdOnBuild $scdOnBuild
    ) {
        $this->resultFactory = $resultFactory;
        $this->environmentReader = $environmentReader;
        $this->scdOnBuild = $scdOnBuild;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $scdOnBuildResult = $this->scdOnBuild->validate();
        if ($scdOnBuildResult instanceof Validator\Result\Error) {
            $scdVariables = [
                StageConfigInterface::VAR_SCD_STRATEGY,
                StageConfigInterface::VAR_SCD_THREADS,
            ];
            $configuredScdVariables = [];

            foreach ($scdVariables as $variableName) {
                if ($this->isVariableConfigured($variableName)) {
                    $configuredScdVariables[] = $variableName;
                }
            }

            if (count($configuredScdVariables)) {
                return $this->resultFactory->error(
                    sprintf(
                        'When %s, static content deployment does not run during the build phase ' .
                        'and the following variables are ignored: %s',
                        $scdOnBuildResult->getError(),
                        implode(', ', $configuredScdVariables)
                    ),
                    '',
                    Error::WARN_SCD_OPTIONS_IGNORANCE
                );
            }
        }

        return $this->resultFactory->success();
    }

    /**
     * Checks that variable is configured in .magento.env.yaml in build section.
     *
     * @param string $variableName
     * @return bool
     */
    private function isVariableConfigured(string $variableName): bool
    {
        try {
            $stageConfig = $this->environmentReader->read()[StageConfigInterface::SECTION_STAGE] ?? [];
            $buildConfig = $stageConfig[StageConfigInterface::STAGE_BUILD] ?? [];

            if (isset($buildConfig[$variableName])) {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }
}
