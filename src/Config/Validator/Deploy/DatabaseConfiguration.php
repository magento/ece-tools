<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates DATABASE_CONFIGURATION variable
 */
class DatabaseConfiguration implements ValidatorInterface
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
     * @param ResultFactory $resultFactory
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ResultFactory $resultFactory,
        DeployInterface $stageConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $dbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        if (empty($dbConfig) || !empty($dbConfig[StageConfigInterface::OPTION_MERGE])) {
            return $this->resultFactory->success();
        }

        if (!isset(
            $dbConfig['connection']['default']['host'],
            $dbConfig['connection']['default']['dbname'],
            $dbConfig['connection']['default']['username'],
            $dbConfig['connection']['default']['password']
        )) {
            return $this->resultFactory->error(
                sprintf('Variable %s is not configured properly', DeployInterface::VAR_DATABASE_CONFIGURATION),
                'At least host, dbname, username and password options must be configured for default connection'
            );
        }

        return $this->resultFactory->success();
    }
}
