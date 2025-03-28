<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Detector;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Service\ServiceInterface;

/**
 * Detects database type depends on variables
 */
class DatabaseType
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var array
     */
    private $variables;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(
        ConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Returns database type depends on version variables
     *
     * @return string
     */
    public function getServiceName(): string
    {
        $versions = $this->getVersionVariables();

        if (isset($versions['aurora_version'])) {
            return ServiceInterface::NAME_DB_AURORA;
        }

        if (isset($versions['version']) && stripos($versions['version'], 'mariadb') !== false) {
            return ServiceInterface::NAME_DB_MARIA;
        }

        return ServiceInterface::NAME_DB_MYSQL;
    }

    /**
     * Return list of database variables with "version" in name
     *
     * @return array
     */
    private function getVersionVariables(): array
    {
        if ($this->variables === null) {
            $this->variables = [];
            try {
                $versionVariables = $this->connection->select('SHOW VARIABLES LIKE "%version%"');

                foreach ($versionVariables as $versionData) {
                    $this->variables[$versionData['Variable_name']] = $versionData['Value'];
                }
            } catch (\Exception $e) {
            }
        }

        return $this->variables;
    }
}
