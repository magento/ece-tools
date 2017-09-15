<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Connection implements ConnectionInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * @param \PDO $pdo
     * @param LoggerInterface $logger
     */
    public function __construct(\PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
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
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->setFetchMode(
                $this->fetchMode
            );
            $statement->execute();

            return $statement->fetchAll();
        });
    }

    /**
     * @inheritdoc
     */
    public function listTables(): array
    {
        $query = 'SHOW TABLES';

        return $this->run($query, [], function () use ($query) {
            $statement = $this->getPdo()->prepare($query);
            $statement->execute();

            return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
        });
    }

    /**
     * @inheritdoc
     */
    public function bindValues(\PDOStatement $statement, array $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
    }

    /**
     * @param string $query
     * @param array $bindings
     * @param \Closure $closure
     * @return mixed
     */
    private function run(string $query, array $bindings, \Closure $closure)
    {
        $this->logger->info('Query: ' . $query);

        return $closure($query, $bindings);
    }
}
