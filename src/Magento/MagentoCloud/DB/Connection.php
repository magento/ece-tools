<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Config\Environment;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var int
     */
    private $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(LoggerInterface $logger, Environment $environment)
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function query(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->pdo->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->setFetchMode(
                $this->fetchMode
            );

            return $statement->execute();
        });
    }

    /**
     * @inheritdoc
     */
    public function affectingQuery(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->execute();

            return $statement->rowCount();
        });
    }

    /**
     * @inheritdoc
     */
    public function select(string $query, array $bindings = []): array
    {
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
     * @inheritdoc
     */
    public function getPdo(): \PDO
    {
        if (null === $this->pdo) {
            $environment = $this->environment;

            $this->pdo = new \PDO(
                sprintf('mysql:dbname=%s;host=%s', $environment->getDbName(), $environment->getDbHost()),
                $environment->getDbUser(),
                $environment->getDbPassword()
            );
        }

        return $this->pdo;
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

        if ($bindings) {
            $this->logger->info('Query bindings: ' . var_export($bindings, true));
        }

        return $closure($query, $bindings);
    }
}
