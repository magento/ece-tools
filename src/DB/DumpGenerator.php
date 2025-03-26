<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;

/**
 * Creates database dump and archives it
 */
class DumpGenerator
{
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
     * @param DumpInterface $dump
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DumpInterface $dump,
        LoggerInterface $logger,
        ShellInterface $shell,
        DirectoryList $directoryList
    ) {
        $this->dump = $dump;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
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
     * @param string $alternativeDestination
     *
     * @throws UndefinedPackageException
     * @throws ShellException
     */
    public function create(
        string $database,
        ConnectionInterface $connectionData,
        bool $removeDefiners,
        string $alternativeDestination
    ) {
        $dumpFileName = sprintf(self::DUMP_FILE_NAME_TEMPLATE, $database, time());
        $temporaryDirectory = !empty($alternativeDestination) ? $alternativeDestination
            : $this->directoryList->getVar();
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
                $this->shell->execute('rm -rf ' . $dumpFile);
            }
            throw $exception;
        } finally {
            fclose($lockFileHandle);
        }
    }
}
