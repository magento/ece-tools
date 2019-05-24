<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

/**
 * General Connection interface.
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
     * Select results with a query.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * Select one result with a query.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function selectOne(string $query, array $bindings = []): array;

    /**
     * List existing tables.
     *
     * @return array
     */
    public function listTables(): array;

    /**
     * Bind values to statement.
     *
     * @param \PDOStatement $statement
     * @param array $bindings
     * @return mixed
     */
    public function bindValues(\PDOStatement $statement, array $bindings);

    /**
     * Retrieve a \PDO object.
     *
     * @return \PDO
     */
    public function getPdo(): \PDO;

    /**
     * Close connection.
     *
     * @return void
     */
    public function close();

    /**
     * Generates table name based on additional settings like `table_prefix`
     *
     * @param string $name
     * @return string
     */
    public function getTableName(string $name): string;
}
