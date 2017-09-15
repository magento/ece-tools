<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

interface ConnectionInterface
{
    /**
     * @return \PDO
     */
    public function getPdo(): \PDO;

    /**
     * @param string $query
     * @param array $bindings
     * @param int $fetchMode
     * @return array
     */
    public function select(string $query, array $bindings = [], int $fetchMode = \PDO::FETCH_ASSOC): array;

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
}
