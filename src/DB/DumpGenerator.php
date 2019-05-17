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

/**
 * Creates database dump and archives it
 */
class DumpGenerator
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
     * Creates database dump and archives it.
     *
     * Lock file is created at the beginning of dumping.
     * This file has dual purpose, it creates a lock, so another DB backup process cannot be executed,
     * as well as serves a log with the name of created dump file.
     * If any error happened during dumping, dump file is removed.
     *
     * @param bool $removeDefiners
     * @return void
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function create(bool $removeDefiners)
    {
        $dumpFileName = sprintf(self::DUMP_FILE_NAME_TEMPLATE, time());

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

        try {
            if (flock($lockFileHandle, LOCK_EX)) {
                $this->logger->info('Start creation DB dump...');

                $command = 'timeout ' . self::DUMP_TIMEOUT . ' ' . $this->dump->getCommand();
                if ($removeDefiners) {
                    $command .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
                }
                $command .= ' | gzip > ' . $dumpFile;

                $process = $this->shell->execute('bash -c "set -o pipefail; ' . $command . '"');

                if ($process->getExitCode() !== ShellInterface::CODE_SUCCESS) {
                    $this->logger->error('Error has occurred during mysqldump');
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
            $this->logger->error($e->getMessage());
            fclose($lockFileHandle);
        }
    }
}
