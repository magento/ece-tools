<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates RESOURCE_CONFIGURATION variable
 */
class ResourceConfiguration implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @param ResultFactory $resultFactory
     * @param MergedConfig $mergedConfig
     */
    public function __construct(
        ResultFactory $resultFactory,
        MergedConfig $mergedConfig
    )
    {
        $this->resultFactory = $resultFactory;
        $this->mergedConfig = $mergedConfig;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->mergedConfig->get();
        $wrongResources = [];
        foreach ($config[MergedConfig::KEY_RESOURCE] as $resourceName => $resourceData) {
            if (!isset($resourceData['connection'])
                || !isset($config[MergedConfig::KEY_DB][MergedConfig::KEY_CONNECTION][$resourceData[MergedConfig::KEY_CONNECTION]])) {
                $wrongResources[] = $resourceName;
            }
        }

        if ($wrongResources) {
            return $this->resultFactory->error(
                sprintf('Variable %s is not configured properly', DeployInterface::VAR_RESOURCE_CONFIGURATION),
                sprintf('Add correct connection information to the following resources: %s', implode(', ', $wrongResources))
            );
        }

        return $this->resultFactory->success();
    }
}
