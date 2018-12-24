<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
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
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @param ResultFactory $resultFactory
     * @param ResourceConfig $resourceConfig
     */
    public function __construct(
        ResultFactory $resultFactory,
        ResourceConfig $resourceConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $wrongResources = [];
        foreach ($this->resourceConfig->get() as $resourceName => $resourceData) {
            if (!isset($resourceData['connection'])) {
                $wrongResources[] = $resourceName;
            }
        }

        if ($wrongResources) {
            return $this->resultFactory->error(
                sprintf('Variable %s is not configured properly', DeployInterface::VAR_RESOURCE_CONFIGURATION),
                sprintf('Add connection information to the following resources: %s', implode(', ', $wrongResources))
            );
        }

        return $this->resultFactory->success();
    }
}
