<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface as DatabaseConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Connection implements ConnectionInterface
{
    const MYSQL_ERROR_CODE_SERVER_GONE_AWAY = 2006;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var integer
     */
    private $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * @var DatabaseConnectionInterface
     */
    private $connectionData;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(LoggerInterface $logger, ConnectionFactory $connectionFactory)
    {
        $this->logger = $logger;
        $this->connectionData = $connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);
    }

    /**
     * @inheritdoc
     */
    public function query(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

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
        return $this->getFetchStatement($query, $bindings)->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function selectOne(string $query, array $bindings = []): array
    {
        return $this->getFetchStatement($query, $bindings)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves prepared fetch statement.
     *
     * @param string $query
     * @param array $bindings
     * @return \PDOStatement
     */
    private function getFetchStatement(string $query, array $bindings = []): \PDOStatement
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->setFetchMode(
                $this->fetchMode
            );
            $statement->execute();

            return $statement;
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
        $this->connect();

        try {
            $this->pdo->query('SELECT 1');
        } catch (\Exception $e) {
            if ($this->pdo->errorInfo()[1] !== self::MYSQL_ERROR_CODE_SERVER_GONE_AWAY) {
                throw $e;
            }

            $this->logger->notice('Lost connection to Mysql server. Reconnecting.');
            $this->pdo = null;
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Create PDO connection.
     */
    private function connect()
    {
        if ($this->pdo instanceof \PDO) {
            return;
        }

        $this->pdo = new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s',
                $this->connectionData->getDbName(),
                $this->connectionData->getHost()
            ),
            $this->connectionData->getUser(),
            $this->connectionData->getPassword(),
            [
                \PDO::ATTR_PERSISTENT => true,
            ]
        );
    }

    /**
     * @param string $query
     * @param array $bindings
     * @param \Closure $closure
     * @return mixed
     */
    private function run(string $query, array $bindings, \Closure $closure)
    {
        $this->logger->debug('Query: ' . $query);

        if ($bindings) {
            $this->logger->debug('Query bindings: ' . var_export($bindings, true));
        }

        return $closure($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function count(string $query, array $bindings = []): int
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
    public function close()
    {
        $this->pdo = null;
    }
}
