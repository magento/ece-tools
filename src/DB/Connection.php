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
     * @var \PDO|null
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
        $result = $this->getFetchStatement($query, $bindings)->fetch(\PDO::FETCH_ASSOC);

        if ($result === false) {
            $this->logger->error('Failed to execute query: ' . var_export($this->getPdo()->errorInfo(), true));

            $result = [];
        }

        return $result;
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
     * {@inheritdoc}
     *
     * @throws PDOException
     * @codeCoverageIgnore
     */
    public function getPdo(): \PDO
    {
        $this->connect();

        try {
            $this->pdo->query('SELECT 1');
        } catch (\Exception $exception) {
            if ($this->pdo->errorInfo()[1] !== self::MYSQL_ERROR_CODE_SERVER_GONE_AWAY) {
                throw new PDOException($exception->getMessage(), $exception->getCode(), $exception);
            }

            $this->logger->notice('Lost connection to Mysql server. Reconnecting.');
            $this->pdo = null;
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Create PDO connection.
     *
     * @codeCoverageIgnore
     */
    private function connect()
    {
        if ($this->pdo instanceof \PDO) {
            return;
        }

        $connectionData = $this->connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);
        $this->pdo = new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s',
                $connectionData->getDbName(),
                $connectionData->getHost()
            ),
            $connectionData->getUser(),
            $connectionData->getPassword(),
            [\PDO::ATTR_PERSISTENT => true] + $connectionData->getDriverOptions()
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
    public function close()
    {
        $this->pdo = null;
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
