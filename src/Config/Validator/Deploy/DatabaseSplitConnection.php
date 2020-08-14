<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates existence the split database connections in DATABASE CONFIGURATION variable
 */
class DatabaseSplitConnection implements ValidatorInterface
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
     * @throws ConfigException
     */
    public function validate(): Validator\ResultInterface
    {
        $config = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        $messageItem = [];
        foreach (DbConfig::CONNECTION_TYPES as $type) {
            foreach (DbConfig::SPLIT_CONNECTIONS as $name) {
                if (isset($config[$type][$name])) {
                    $messageItem[] = "- $type: $name";
                }
            }
        }

        if (empty($messageItem)) {
            return $this->resultFactory->success();
        }

        return $this->resultFactory->error(
            sprintf(
                'Detected split database configuration in the %s property of the file .magento.env.yaml:' . PHP_EOL
                . '%s' . PHP_EOL
                . 'Magento Cloud does not support custom connections in the split database configuration,'
                . ' Custom connections will be ignored',
                DeployInterface::VAR_DATABASE_CONFIGURATION,
                implode(PHP_EOL, $messageItem)
            ),
            sprintf(
                'Update the %s variable in the .magento.env.yaml file to remove custom connections from the split database configuration.',
                DeployInterface::VAR_DATABASE_CONFIGURATION
            ),
            Error::WARN_WRONG_SPLIT_DB_CONFIG
        );
    }
}
