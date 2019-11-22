<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates the database split connections in DATABASE_CONFIGURATION variable
 */
class DatabaseSplitConnection implements ValidatorInterface
{
    const SPLIT_CONNECTIONS = ['sale', 'checkout'];

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
        $splitConnections = [];

        foreach (array_keys($dbConfig['connection'] ?? []) as $connection) {
            if (in_array($connection, self::SPLIT_CONNECTIONS)) {
                $splitConnections['connection'][] = $connection;
            }
        }

        foreach (array_keys($dbConfig['slave_connection'] ?? []) as $connection) {
            if (in_array($connection, self::SPLIT_CONNECTIONS)) {
                $splitConnections['slave_connection'][] = $connection;
            }
        }

        if ($splitConnections) {
            $messages[] = sprintf(
                'Split database configuration was detected in the property %s'
                . ' of the file .magento.env.yaml:',
                DeployInterface::VAR_DATABASE_CONFIGURATION
            );
            foreach ($splitConnections as $type => $connections) {
                $messages[] = "- $type: " . implode(', ', $connections);
            }
            $messages[] = 'Split database configuration will be ignored in during current deploy phase.';
            $messages[] = 'Magento Cloud not support a custom split database configuration';
            return $this->resultFactory->error(implode(PHP_EOL, $messages));
        }
        return $this->resultFactory->success();
    }
}
