<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;

/**
 * Creates database dump and archives it
 */
class DumpGenerator
{
    /**
     * Database names
     */
    const DATABASE_MAIN = 'main';
    const DATABASE_QUOTE = 'quote';
    const DATABASE_SALES = 'sales';

    /**
     * Database name map:
     * {connection name from environment} => {database name}
     */
    const DATABASE_MAP = [
        ConnectionFactory::CONNECTION_SLAVE => self::DATABASE_MAIN,
        ConnectionFactory::CONNECTION_QUOTE_SLAVE => self::DATABASE_QUOTE,
        ConnectionFactory::CONNECTION_SALES_SLAVE => self::DATABASE_SALES,
    ];

    /**
     * Connection map:
     * {connection name from environment} => {magento connection name}
     */
    const CONNECTION_MAP = [
        ConnectionFactory::CONNECTION_SLAVE => DbConfig::CONNECTION_DEFAULT,
        ConnectionFactory::CONNECTION_QUOTE_SLAVE => DbConfig::CONNECTION_CHECKOUT,
        ConnectionFactory::CONNECTION_SALES_SLAVE => DbConfig::CONNECTION_SALES,
    ];

    /**
     * Template for dump file name where first %s should be changed to database name
     * and second %s should be changed to timestamp for uniqueness
     */
    const DUMP_FILE_NAME_TEMPLATE = 'dump-%s-%s.sql.gz';

    /**
     * Lock file name.
     * During the dumping this file is locked to prevent running dump by others.
     */
    const LOCK_FILE_NAME = 'dbdump.lock';

    /**
     * Timeout for mysqldump command in seconds
     */
    const DUMP_TIMEOUT = 3600;

    /**
     * Used for execution shell operations
     *
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DumpInterface
     */
    private $dump;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * Factory for creation database data connection classes
     *
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param DumpInterface $dump
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     * @param DbConfig $dbConfig ,
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        DumpInterface $dump,
        LoggerInterface $logger,
        ShellInterface $shell,
        DirectoryList $directoryList,
        DbConfig $dbConfig,
        ConnectionFactory $connectionFactory
    ) {
        $this->dump = $dump;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
        $this->dbConfig = $dbConfig;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * Creates databases dumps
     *
     * @param bool $removeDefiners
     * @param array $databases
     * @return void
     * @throws ConfigException
     * @throws UndefinedPackageException
     */
    public function create(bool $removeDefiners, array $databases = [])
    {
        if (empty($databases)) {
            $connections = array_values(array_intersect_key(
                array_flip(self::CONNECTION_MAP),
                $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? []
            ));
        } else {
            if (!$this->validateDatabaseNames($databases)) {
                return;
            }
            $connections = array_keys(array_intersect(self::DATABASE_MAP, $databases));
            if (!$this->checkConnectionsAvailability($connections)) {
                return;
            }
        }

        foreach ($connections as $connection) {
            $this->process(
                self::DATABASE_MAP[$connection],
                $this->connectionFactory->create($connection),
                $removeDefiners
            );
        }
    }

    /**
     * Validates database names
     *
     * @param array $databases
     * @return bool
     */
    private function validateDatabaseNames(array $databases): bool
    {
        $invalidDatabaseNames = array_diff($databases, self::DATABASE_MAP);
        if (!empty($invalidDatabaseNames)) {
            $this->logger->error(sprintf(
                'Incorrect the database names:[ %s ]. Available database names: [ %s ]',
                implode(' ', $invalidDatabaseNames),
                implode(' ', self::DATABASE_MAP)
            ));
            return false;
        }
        return true;
    }

    /**
     * Checks availability of connections
     *
     * @param array $connections
     * @return bool
     * @throws ConfigException
     */
    private function checkConnectionsAvailability(array $connections): bool
    {
        $result = true;
        $envConnections = $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? [];
        foreach ($connections as $connection) {
            if (!isset($envConnections[self::CONNECTION_MAP[$connection]])) {
                $this->logger->error(sprintf(
                    'Environment has not connection `%s` associated with database `%s`',
                    self::CONNECTION_MAP[$connection],
                    self::DATABASE_MAP[$connection]
                ));
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Creates database dump and archives it for each database.
     *
     * Lock file is created at the beginning of dumping.
     * This file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
     * as well as serves a log with the name of created dump file.
     * If any error happened during dumping, dump file is removed.
     *
     * @param string $database
     * @param ConnectionInterface $connectionData
     * @param bool $removeDefiners
     *
     * @throws UndefinedPackageException
     * @throws ShellException
     */
    private function process(string $database, ConnectionInterface $connectionData, bool $removeDefiners)
    {
        $dumpFileName = sprintf(self::DUMP_FILE_NAME_TEMPLATE, $database, time());
        $temporaryDirectory = sys_get_temp_dir();
        $dumpFile = $temporaryDirectory . '/' . $dumpFileName;
        $lockFile = $this->directoryList->getVar() . '/' . self::LOCK_FILE_NAME;

        // Lock file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
        // as well as serves as a log with the name of created dump file.
        $lockFileHandle = fopen($lockFile, "w+");

        // Lock the sql dump so staging sync doesn't start using it until we're done.
        $this->logger->info('Waiting for lock on db dump.');

        if ($lockFileHandle === false) {
            $this->logger->error('Could not get the lock file!');
            return;
        }

        if (!flock($lockFileHandle, LOCK_EX)) {
            $this->logger->info('Dump process is locked!');
            return;
        }

        $this->logger->info(sprintf('Start creation DB dump for %s database...', $database));

        $command = 'timeout ' . self::DUMP_TIMEOUT . ' ' . $this->dump->getCommand($connectionData);
        if ($removeDefiners) {
            $command .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
        }
        $command .= ' | gzip > ' . $dumpFile;

        try {
            $this->shell->execute('bash -c "set -o pipefail; ' . $command . '"');
            $this->logger->info(sprintf(
                'Finished DB dump for %s database, it can be found here: %s',
                $database,
                $dumpFile
            ));
            fwrite(
                $lockFileHandle,
                sprintf('[%s] Dump was written in %s', date("Y-m-d H:i:s"), $dumpFile) . PHP_EOL
            );
            fflush($lockFileHandle);
            flock($lockFileHandle, LOCK_UN);
        } catch (ShellException $exception) {
            if (file_exists($dumpFile)) {
                $this->shell->execute('rm -rf' . $dumpFile);
            }
            throw $exception;
        } finally {
            fclose($lockFileHandle);
        }
    }
}
