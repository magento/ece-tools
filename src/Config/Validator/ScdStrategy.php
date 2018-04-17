<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates correctness of scd strategy option.
 */
class ScdStrategy implements ValidatorInterface
{
    /**
     * @var array
     */
    private $possibleStrategy = ['compact', 'quick', 'standard'];

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param ResultFactory $resultFactory
     * @param GlobalConfig $globalConfig
     * @param StageConfigInterface $stageConfig
     */
    public function __construct(
        ResultFactory $resultFactory,
        GlobalConfig $globalConfig,
        StageConfigInterface $stageConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->globalConfig = $globalConfig;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Validates that scd strategy has correct value.
     *
     * @return ResultInterface
     */
    public function validate(): ResultInterface
    {
        if (!$this->globalConfig->get(DeployInterface::VAR_SCD_ON_DEMAND)
            && !in_array($this->stageConfig->get(StageConfigInterface::VAR_SCD_STRATEGY), $this->possibleStrategy)
        ) {
            return $this->resultFactory->create(
                ResultInterface::ERROR,
                [
                    'error' => 'Wrong value of SCD_STRATEGY option.',
                    'suggestion' => 'Please use one of possible strategies: ' . implode(', ', $this->possibleStrategy)
                ]
            );
        }

        return $this->resultFactory->create(ResultInterface::SUCCESS);
    }
}
