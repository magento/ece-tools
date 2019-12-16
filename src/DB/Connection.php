<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Connection implements ConnectionInterface
{
    const MYSQL_ERROR_CODE_SERVER_GONE_AWAY = 2006;

    /**
     * @var \PDO[]
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
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var string
     */
    private $tablePrefix;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionFactory $connectionFactory
     * @param DbConfig $dbConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionFactory $connectionFactory,
        DbConfig $dbConfig
    ) {
        $this->logger = $logger;
        $this->connectionFactory = $connectionFactory;
        $this->dbConfig = $dbConfig;
    }

    /**
     * @inheritdoc
     */
    public function query(
        string $query,
        array $bindings = [],
        string $connection = ConnectionFactory::CONNECTION_MAIN
    ): bool {
        return $this->run($query, $bindings, $connection, function ($query, $bindings, $connection) {
            $statement = $this->getPdo($connection)->prepare($query);

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
    public function affectingQuery(
        string $query,
        array $bindings = [],
        string $connection = ConnectionFactory::CONNECTION_MAIN
    ): int {
        return $this->run($query, $bindings, $connection, function ($query, $bindings, $connection) {
            $statement = $this->getPdo($connection)->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->execute();

            return $statement->rowCount();
        });
    }

    /**
     * @inheritdoc
     */
    public function select(
        string $query,
        array $bindings = [],
        string $connection = ConnectionFactory::CONNECTION_MAIN
    ): array {
        return $this->getFetchStatement($query, $bindings, $connection)->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function selectOne(
        string $query,
        array $bindings = [],
        string $connection = ConnectionFactory::CONNECTION_MAIN
    ): array {
        $result = $this->getFetchStatement($query, $bindings, $connection)->fetch(\PDO::FETCH_ASSOC);

        if ($result === false) {
            $message = 'Failed to execute query: ' . var_export($this->getPdo($connection)->errorInfo(), true);
            $this->logger->error($message);

            $result = [];
        }

        return $result;
    }

    /**
     * Retrieves prepared fetch statement.
     *
     * @param string $query
     * @param array $bindings
     * @param string $connection
     * @return \PDOStatement
     */
    private function getFetchStatement(
        string $query,
        array $bindings = [],
        string $connection = ConnectionFactory::CONNECTION_MAIN
    ): \PDOStatement {
        return $this->run($query, $bindings, $connection, function ($query, $bindings, $connection) {
            $statement = $this->getPdo($connection)->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->setFetchMode(
                $this->fetchMode
            );
            $statement->execute();

            return $statement;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string $connection
     * @retun @array
     */
    public function listTables(string $connection = ConnectionFactory::CONNECTION_MAIN): array
    {
        $query = 'SHOW TABLES';

        return $this->run($query, [], $connection, function () use ($query, $connection) {
            $statement = $this->getPdo($connection)->prepare($query);
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
     * {@inheritdoc}
     * @param string $connection
     * @throws PDOException
     * @codeCoverageIgnore
     */
    public function getPdo(string $connection = ConnectionFactory::CONNECTION_MAIN): \PDO
    {
        $this->connect($connection);

        try {
            $this->pdo[$connection]->query('SELECT 1');
        } catch (\Exception $exception) {
            if ($this->pdo[$connection]->errorInfo()[1] !== self::MYSQL_ERROR_CODE_SERVER_GONE_AWAY) {
                throw new PDOException($exception->getMessage(), $exception->getCode(), $exception);
            }

            $this->logger->notice('Lost connection to Mysql server. Reconnecting.');
            unset($this->pdo[$connection]);
            $this->connect($connection);
        }

        return $this->pdo[$connection];
    }

    /**
     * Create PDO connection.
     *
     * @param string $connection
     *
     * @codeCoverageIgnore
     */
    private function connect(string $connection)
    {
        if (isset($this->pdo[$connection]) && $this->pdo[$connection] instanceof \PDO) {
            return;
        }

        $connectionData = $this->connectionFactory->create($connection);
        $this->pdo[$connection] = new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s',
                $connectionData->getDbName(),
                $connectionData->getHost()
            ),
            $connectionData->getUser(),
            $connectionData->getPassword(),
            [
                \PDO::ATTR_PERSISTENT => true,
            ]
        );
    }

    /**
     * @param string $query
     * @param array $bindings
     * @param string $connection
     * @param \Closure $closure
     * @return mixed
     */
    private function run(string $query, array $bindings, string $connection, \Closure $closure)
    {
        $this->logger->debug("Connection: $connection. Query: $query");

        if ($bindings) {
            $message = "Connection: $connection. Query bindings: " . var_export($bindings, true);
            $this->logger->debug($message);
        }

        return $closure($query, $bindings, $connection);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $connection
     */
    public function close(string $connection = ConnectionFactory::CONNECTION_MAIN)
    {
        unset($this->pdo[$connection]);
    }

    /**
     * @inheritdoc
     */
    public function getTableName(string $name): string
    {
        if (!empty($this->getTablePrefix())) {
            $name = $this->getTablePrefix() . $name;
        }

        return $name;
    }

    /**
     * Returns table_prefix value.
     *
     * @return string
     */
    private function getTablePrefix(): string
    {
        if ($this->tablePrefix === null) {
            $this->tablePrefix = $this->dbConfig->get()['table_prefix'] ?? '';
        }

        return $this->tablePrefix;
    }
}
