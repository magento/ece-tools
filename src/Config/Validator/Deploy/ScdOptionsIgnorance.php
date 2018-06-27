<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Checks that scd configuration is really using on deploy phase.
 *
 * For example, if SCD_STRATEGY is configured for deploy phase, but static won't generates on deploy phase,
 * this validator returns appropriate message.
 */
class ScdOptionsIgnorance implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Variable\ConfigurationChecker
     */
    private $configurationChecker;

    /**
     * @var Validator\GlobalStage\ScdOnDeploy
     */
    private $scdOnDeploy;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Validator\GlobalStage\ScdOnDeploy $scdOnDeploy
     * @param Variable\ConfigurationChecker $configurationChecker
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Validator\GlobalStage\ScdOnDeploy $scdOnDeploy,
        Variable\ConfigurationChecker $configurationChecker
    ) {
        $this->resultFactory = $resultFactory;
        $this->scdOnDeploy = $scdOnDeploy;
        $this->configurationChecker = $configurationChecker;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $scdOnDeployResult = $this->scdOnDeploy->validate();
        if ($scdOnDeployResult instanceof Validator\Result\Error) {
            $scdVariables = [
                StageConfigInterface::VAR_SCD_STRATEGY,
                StageConfigInterface::VAR_SCD_THREADS,
                StageConfigInterface::VAR_SCD_EXCLUDE_THEMES,
            ];
            $configuredScdVariables = [];

            foreach ($scdVariables as $variableName) {
                if ($this->configurationChecker->isConfigured($variableName)) {
                    $configuredScdVariables[] = $variableName;
                }
            }

            if (count($configuredScdVariables)) {
                return $this->resultFactory->error(sprintf(
                    'When %s, static content deployment does not run during the deploy phase ' .
                    'and the following variables are ignored: %s',
                    $scdOnDeployResult->getError(),
                    implode(', ', $configuredScdVariables)
                ));
            }
        }

        return $this->resultFactory->success();
    }
}
