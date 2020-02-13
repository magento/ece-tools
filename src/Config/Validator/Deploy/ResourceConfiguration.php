<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
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
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @param ResultFactory $resultFactory
     * @param ResourceConfig $resourceConfig
     * @param DbConfig $dbConfig
     */
    public function __construct(
        ResultFactory $resultFactory,
        ResourceConfig $resourceConfig,
        DbConfig $dbConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->dbConfig = $dbConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $dbConfig = $this->dbConfig->get();
        $resourceConfig = $this->resourceConfig->get();
        $wrongResources = [];
        foreach ($resourceConfig as $resourceName => $resourceData) {
            if (!isset($resourceData[ResourceConfig::KEY_CONNECTION])
                || !isset($dbConfig[DbConfig::KEY_CONNECTION][$resourceData[ResourceConfig::KEY_CONNECTION]])
            ) {
                $wrongResources[] = $resourceName;
            }
        }

        if ($wrongResources) {
            return $this->resultFactory->error(
                sprintf(
                    'Variable %s is not configured properly',
                    DeployInterface::VAR_RESOURCE_CONFIGURATION
                ),
                sprintf(
                    'Add correct connection information to the following resources: %s',
                    implode(', ', $wrongResources)
                )
            );
        }

        return $this->resultFactory->success();
    }
}
