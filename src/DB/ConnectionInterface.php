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
     * Read queries.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function query(string $query, array $bindings = []): bool;

    /**
     * State changing queries.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingQuery(string $query, array $bindings = []): int;

    /**
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function selectOne(string $query, array $bindings = []): array;

    /**
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function count(string $query, array $bindings = []): int;

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

    /**
     * Close connection.
     *
     * @return void
     */
    public function close();
}
