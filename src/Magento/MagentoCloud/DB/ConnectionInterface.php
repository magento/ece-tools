<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

/**
 * Interface ConnectionInterface
 *
 * @package Magento\MagentoCloud\DB
 */
interface ConnectionInterface
{
    /**
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function query(string $query, array $bindings = []): bool;

    /**
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * @return array
     */
    public function listTables(): array;

    /**
     * @param \PDOStatement $statement
     * @param array $bindings
     * @return mixed
     */
    public function bindValues(\PDOStatement $statement, array $bindings);

    /**
     * @return \PDO
     */
    public function getPdo(): \PDO;
}
