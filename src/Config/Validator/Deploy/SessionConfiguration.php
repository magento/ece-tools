<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates SESSION_CONFIGURATION variable
 */
class SessionConfiguration implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param ResultFactory $resultFactory
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        ResultFactory $resultFactory,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->resultFactory = $resultFactory;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * @return ResultInterface
     */
    public function validate(): ResultInterface
    {
        $sessionConfig = $this->stageConfig->get(DeployInterface::VAR_SESSION_CONFIGURATION);
        if (empty($sessionConfig) || $this->configMerger->isMergeRequired($sessionConfig)) {
            return $this->resultFactory->success();
        }

        if (!isset($sessionConfig['save'])) {
            return $this->resultFactory->error(
                sprintf('The %s variable is not configured properly', DeployInterface::VAR_SESSION_CONFIGURATION),
                'At least "save" option must be configured for session configuration.'
            );
        }

        return $this->resultFactory->success();
    }
}
