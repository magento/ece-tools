<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\DbDump;

use Magento\MagentoCloud\Config\DbConnectionDataInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Process creates database dump and archives it
 */
class DbDump implements ProcessInterface
{
    /**
     * Last part of dump file name.
     * Timestamp will be added at the beginning for uniqueness.
     */
    const DUMP_FILE_NAME = 'prod.sql.gz';

    /**
     * Lock file name.
     * If this file exists, it means that dumping is in process.
     */
    const LOCK_FILE_NAME = 'dbsync.prod.lock';

    /**
     * Database connection data for read operations.
     *
     * @var DbConnectionDataInterface
     */
    private $dbConnection;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param DbConnectionDataInterface $dbConnection
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        DbConnectionDataInterface $dbConnection,
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->dbConnection = $dbConnection;
        $this->logger = $logger;
        $this->shell = $shell;
    }

    /**
     * Creates database dump.
     *
     * Lock file is created at the beginning of dumping.
     * If file already exists then dumping is running by another process.
     * If eny error happened during dumping, dump file is removed.
     * Lock file is deleted at the end of operation.
     *
     * @return void
     */
    public function execute()
    {
        $uniqueFileSuffix = time() . '-';
        //verified with platform this is the best way to get temporary folder location on all instances
        $temporaryDirectory = sys_get_temp_dir();

        $dumpFile = $temporaryDirectory . '/' . $uniqueFileSuffix . self::DUMP_FILE_NAME;
        $lockFile = $temporaryDirectory . '/' . $uniqueFileSuffix . self::LOCK_FILE_NAME;

        // lock file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
        // as well as serves as a log with the details of the db dump executed.
        $fp = fopen($lockFile, "w+");

        $this->logger->info('Beginning DB dump.');

        # Lock the production sql dump so staging sync doesn't start using it until we're done.
        $this->logger->info('Waiting for lock on prod db dump.');

        if (flock($fp, LOCK_EX)) {
            $this->logger->info('Starting dump...');

            $command = $this->getCommand() . '| gzip > ' . $dumpFile;
            $errors = $this->shell->execute($command);

            if ($errors) {
                $this->logger->info('Error has occurred during mysqldump');
                $this->logger->info(implode(PHP_EOL, $errors));
                $this->shell->execute('rm ' . $dumpFile);

            } else {
                $this->logger->info('Finished DB dump, it can be found here: ' . $dumpFile);
            }
        } else {
            $this->logger->info('Could not get the lock!');
        }
        fclose($fp);
        $this->shell->execute('rm ' . $lockFile);
    }


    /**
     * Returns mysqldump command for executing.
     *
     * @return string
     */
    private function getCommand()
    {
        $command = "timeout 3600 mysqldump -h {$this->dbConnection->getHost()} -P {$this->dbConnection->getPort()}"
            . " -u {$this->dbConnection->getUser()}";
        $password = $this->dbConnection->getPassword();
        if ($password) {
            $command .= " -p{$password}";
        }
        $command .= " {$this->dbConnection->getDbName()} --single-transaction --no-autocommit --quick";

        return $command;
    }
}
