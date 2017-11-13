<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\DbDump;

use Magento\MagentoCloud\Db\Data\ConnectionInterface;
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
    const LOCK_FILE_NAME = 'dbdump.lock';

    /**
     * Database connection data for read operations
     *
     * @var ConnectionInterface
     */
    private $connectionData;

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
     * @param ConnectionInterface $connectionData
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        ConnectionInterface $connectionData,
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->connectionData = $connectionData;
        $this->logger = $logger;
        $this->shell = $shell;
    }

    /**
     * Creates database dump and archives it.
     *
     * Lock file is created at the beginning of dumping.
     * This file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
     * as well as serves a log with the name of created dump file.
     * If any error happened during dumping, dump file is removed.
     *
     * @return void
     */
    public function execute()
    {
        $dumpFileName = sprintf(self::DUMP_FILE_NAME_TEMPLATE, time());

        $temporaryDirectory = sys_get_temp_dir();

        $dumpFile = $temporaryDirectory . '/' . $dumpFileName;
        $lockFile = $temporaryDirectory . '/' . self::LOCK_FILE_NAME;

        // Lock file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
        // as well as serves as a log with the name of created dump file.
        $lockFileHandle = fopen($lockFile, "w+");

        // Lock the sql dump so staging sync doesn't start using it until we're done.
        $this->logger->info('Waiting for lock on db dump.');

        if ($lockFileHandle === false) {
            $this->logger->info('Could not get the lock file!');
            return;
        }

        try {
            if (flock($lockFileHandle, LOCK_EX)) {
                $this->logger->info('Start creation DB dump...');

                $command = $this->getCommand() . ' | gzip > ' . $dumpFile;
                $errors = $this->shell->execute('bash -c "set -o pipefail; ' . $command . '"');

                if ($errors) {
                    $this->logger->info('Error has occurred during mysqldump');
                    $this->shell->execute('rm ' . $dumpFile);
                } else {
                    $this->logger->info('Finished DB dump, it can be found here: ' . $dumpFile);
                    fwrite(
                        $lockFileHandle,
                        sprintf('[%s] Dump was written in %s', date("Y-m-d H:i:s"), $dumpFile) . PHP_EOL
                    );
                    fflush($lockFileHandle);
                }
                flock($lockFileHandle, LOCK_UN);
            } else {
                $this->logger->info('Dump process is locked!');
            }
            fclose($lockFileHandle);
        } catch (\Exception $e) {
            $this->logger->info('ERROR: ' . $e->getMessage());
            fclose($lockFileHandle);
        }
    }

    /**
     * Returns mysqldump command for executing.
     *
     * @return string
     */
    private function getCommand()
    {
        $command = "timeout 3600 mysqldump -h {$this->connectionData->getHost()} -P {$this->connectionData->getPort()}"
            . " -u {$this->connectionData->getUser()}";
        $password = $this->connectionData->getPassword();
        if ($password) {
            $command .= " -p{$password}";
        }
        $command .= " {$this->connectionData->getDbName()} --single-transaction --no-autocommit --quick";

        return $command;
    }
}
