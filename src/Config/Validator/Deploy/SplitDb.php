<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Helper class to check if split db is configured
 */
class SplitDb
{
    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param DeployInterface $stageConfig
     */
    public function __construct(DeployInterface $stageConfig)
    {
        $this->stageConfig = $stageConfig;
    }

    /**
     * Checks if split db is configured in SPLIT_DB or DATABASE_CONFIGURATION options
     *
     * @return bool
     * @throws ConfigException
     */
    public function isConfigured(): bool
    {
        if (!empty($this->stageConfig->get(DeployInterface::VAR_SPLIT_DB))) {
            return true;
        }

        if (!empty($dbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION))) {
            if (isset($dbConfig['connection'][DeployInterface::SPLIT_DB_VALUE_QUOTE]) ||
                isset($dbConfig['connection'][DeployInterface::SPLIT_DB_VALUE_SALES])
            ) {
                return true;
            }
        }

        return false;
    }
}
