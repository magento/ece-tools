<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates the split database connections in DATABASE_CONFIGURATION variable
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
     */
    public function validate(): Validator\ResultInterface
    {
        $dbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        $splitConnections = [];
        foreach (DbConfig::CONNECTION_TYPES as $connectionType) {
            foreach (DbConfig::SPLIT_CONNECTIONS as $splitConnection) {
                if (!isset($dbConfig[$connectionType][$splitConnection])) {
                    continue;
                }
                $splitConnections[$connectionType][] = $splitConnection;
            }
        }

        if (empty($splitConnections)) {
            return $this->resultFactory->success();
        }

        $messages[] = sprintf(
            'Split database configuration was detected in the property %s'
            . ' of the file .magento.env.yaml:',
            DeployInterface::VAR_DATABASE_CONFIGURATION
        );
        foreach ($splitConnections as $type => $connections) {
            $messages[] = "- $type: " . implode(', ', $connections);
        }
        $messages[] = 'Custom split database configuration will be ignored in during current deploy phase.';
        $messages[] = 'Magento Cloud not support a custom split database configuration';

        return $this->resultFactory->error(implode(PHP_EOL, $messages));
    }
}
