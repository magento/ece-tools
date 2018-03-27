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
     * @var Environment
     */
    private $environment;

    /**
     * @var integer
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

        $environment = $this->environment;

        $this->pdo = new \PDO(
            sprintf('mysql:dbname=%s;host=%s', $environment->getDbName(), $environment->getDbHost()),
            $environment->getDbUser(),
            $environment->getDbPassword(),
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
        $this->logger->info('Query: ' . $query);

        if ($bindings) {
            $this->logger->info('Query bindings: ' . var_export($bindings, true));
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
}
