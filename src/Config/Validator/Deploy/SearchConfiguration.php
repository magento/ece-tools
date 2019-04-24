<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates SEARCH_CONFIGURATION variable
 */
class SearchConfiguration implements ValidatorInterface
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
     * Checks that SEARCH_CONFIGURATION variable contains at least 'engine' option if _merge was not set
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $searchConfig = $this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);
        if ($this->configMerger->isEmpty($searchConfig) || $this->configMerger->isMergeRequired($searchConfig)) {
            return $this->resultFactory->success();
        }

        if (!isset($searchConfig['engine'])) {
            return $this->resultFactory->error(
                sprintf('Variable "%s" is not configured properly', DeployInterface::VAR_SEARCH_CONFIGURATION),
                'At least engine option must be configured'
            );
        }

        return $this->resultFactory->success();
    }
}
