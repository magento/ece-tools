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
     * Template for dump file name where %s should be changed to timestamp for uniqueness
     */
    const DUMP_FILE_NAME_TEMPLATE = 'dump-%s.sql.gz';

    /**
     * Lock file name.
     * During the dumping this file is locked to prevent running dump by others.
     */
    const LOCK_FILE_NAME = 'dbdump.%s.lock';

    /**
     * Database connection data for read operations
     *
     * @var DbConnectionDataInterface
     */
    private $dbConnection;

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
     * Creates database dump and archive it.
     *
     * Lock file is created at the beginning of dumping.
     * If file already exists and locked then dumping is running by another process and current procees wait for unlock.
     * If eny error happened during dumping, dump file is removed.
     * Path to the created dump file is written to lock file.
     *
     * @return void
     */
    public function execute()
    {
        $dumpFileName = sprintf(self::DUMP_FILE_NAME_TEMPLATE, time());
        //verified with platform this is the best way to get temporary folder location on all instances
        $temporaryDirectory = sys_get_temp_dir();

        $dumpFile = $temporaryDirectory . '/' . $dumpFileName;
        $lockFile = $temporaryDirectory . '/' . self::LOCK_FILE_NAME;

        // lock file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
        // as well as serves as a log with the details of the db dump executed.
        $fp = fopen($lockFile, "w+");

        # Lock the sql dump so staging sync doesn't start using it until we're done.
        $this->logger->info('Waiting for lock on db dump.');

        if (flock($fp, LOCK_EX)) {
            $this->logger->info('Start creation DB dump...');

            $command = $this->getCommand() . '| gzip > ' . $dumpFile;
            $errors = $this->shell->execute('bash -c "set -o pipefail; ' . $command . '" 2>&1');

            if ($errors) {
                $this->logger->info('Error has occurred during mysqldump');
                $this->shell->execute('rm ' . $dumpFile);

            } else {
                $this->logger->info('Finished DB dump, it can be found here: ' . $dumpFile);
                fwrite($fp, sprintf('[%s] Dump was written in %s', date("Y-m-d H:i:s"), $dumpFile) . PHP_EOL);
                fflush($fp);
            }
            flock($fp, LOCK_UN);
        } else {
            $this->logger->info('Could not get the lock!');
        }
        fclose($fp);
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
