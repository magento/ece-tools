<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\DB\ConnectionInterface;

/**
 * Returns Aurora service configurations.
 */
class Aurora implements ServiceInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $version;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(
        ConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return [];
    }

    /**
     * Retrieves Aurora service version.
     * Returns '0' if database is not Aurora.
     *
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            try {
                $rawVersion = $this->connection->selectOne('SELECT AURORA_VERSION() as version');
                preg_match('/^\d+\.\d+/', $rawVersion['version'] ?? '', $matches);

                $this->version = $matches[0] ?? '0';
            } catch (\Exception $e) {
                throw new ServiceException($e->getMessage());
            }
        }

        return $this->version;
    }
}
