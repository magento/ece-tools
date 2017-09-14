<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

class Connection implements ConnectionInterface
{
    private $pdo;

    /**
     * @var int
     */
    private $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @inheritdoc
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * @inheritdoc
     */
    public function select(
        string $query,
        array $bindings = [],
        int $fetchMode = \PDO::FETCH_ASSOC
    ): array {
        $statement = $this->getPdo()->prepare($query);

        $this->bindValues($statement, $bindings);

        $statement->setFetchMode(
            $this->fetchMode
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function listTables(): array
    {
        $statement = $this->getPdo()->prepare('SHOW TABLES');
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @inheritdoc
     */
    public function bindValues(\PDOStatement $statement, array $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
    }
}
