<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\DBDump;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DBDump implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(Environment $environment, LoggerInterface $logger, ShellInterface $shell)
    {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->shell = $shell;
    }

    /**
     * Create a dump of the database safely
     */
    public function execute()
    {
        $timestamp = time();
        //verified with platform this is the best way to get temporary folder location on all instances
        $temporaryDirectory = sys_get_temp_dir();

        // lock file has dual purpose, it creates a lock, so another DB backup process cannot be executed, as well as
        // serves as a log with the details of the db dump executed.
        $lockFile = "{$timestamp}-dbsync.prod.lock";
        $dbDumpAttemptFile = "{$timestamp}-prod-attempt.sql.gz";
        $dbDumpLatestFile = "{$timestamp}-prod-latest.sql.gz";

        touch("{$temporaryDirectory}/{$lockFile}");
        chmod("{$temporaryDirectory}/{$lockFile}", 0755);
        $fp = fopen("{$temporaryDirectory}/{$lockFile}", "w+");
        chdir(__DIR__);

        $this->log($fp, 'Beginning production db dump.');

        # Lock the production sql dump so staging sync doesn't start using it until we're done.
        $this->log($fp, 'Waiting for lock on prod db dump.');

        if (flock($fp, LOCK_EX)) {
            $this->log($fp, 'Got the lock!');
            $this->log($fp, 'Starting dump...');

            # The actual db dump script.
            $command = $this->getMySQLDumpCommand($fp);
            $command .= " | gzip > {$temporaryDirectory}/{$dbDumpAttemptFile}";

            $this->log($fp, 'Executing the db backup command');
            $pipeStatus = $this->shell->execute($command);

            if (count($pipeStatus) == 0) {
                $this->log($fp, 'Success, renaming dump to final location.');
                chmod("{$temporaryDirectory}/{$dbDumpAttemptFile}", 0755);
                $this->shell->execute("mv {$temporaryDirectory}/{$dbDumpAttemptFile} "
                    . "{$temporaryDirectory}/{$dbDumpLatestFile}");

                fflush($fp);
                flock($fp, LOCK_UN);
                $this->log($fp, 'Finished production db dump, it can be found here: '
                    . "{$temporaryDirectory}/{$dbDumpLatestFile}");
            } else {
                $this->log($fp, 'Error has occurred during mysqldump');
                foreach ($pipeStatus as $pipeLine) {
                    $this->log($fp, $pipeLine);
                }
            }
        } else {
            $this->log($fp, "Couldn't get the lock!");
        }
        fclose($fp);
    }

    /**
     * Create a database connection string based on current instance
     *
     * @param resource $fp file pointer to a lock/log file
     * @return string command to be executed
     */
    private function getMySQLDumpCommand($fp)
    {
        $relationships = $this->environment->getRelationships();

        $dbHost = $relationships["database"][0]["host"];
        $dbName = $relationships["database"][0]["path"];
        $dbUser = $relationships["database"][0]["username"];
        $dbPassword = $relationships["database"][0]["password"];
        $dbPort = '3307';

        if (empty($_ENV['REGISTRY'])) { #for integration
            $dbPort = '3306';
        } else { #for for stg/prod
            $db = new \PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword);
            $current_active = $db->query("SELECT @@hostname;")->fetchColumn();
            $this_host = gethostname();
            $alternate_ip = preg_replace('~\.5\.5$~', '.6.6', gethostbyname($this_host));
            $alternate_host = gethostbyaddr($alternate_ip);

            if ($current_active == $this_host) {
                // Use a different DB host.
                $this->log($fp, "Using host #2 ($alternate_host) for database dump as #1 is the active master."
                    . PHP_EOL);
                $dbHost = $alternate_host;
            } else {
                $this->log($fp, "Using host #1 ($this_host) for database dump..." . PHP_EOL);
                $dbHost = '127.0.0.1';
            }
        }

        # The actual dump script.
        $command = "timeout 3600 mysqldump -h {$dbHost} -P {$dbPort} ";
        #for stg/prod
        if (!empty($dbPassword)) {
            $command .= " -p{$dbPassword} ";
        }
        $command .= "-u {$dbUser} $dbName --single-transaction --no-autocommit --quick ";

        return $command;
    }

    /**
     * Log output to console as well as lock file
     *
     * @param resource $fp file pointer to a lock/log file
     * @param string $message Log message
     *
     * @return void
     */
    private function log($fp, $message)
    {
        $this->logger->info($message);
        fwrite($fp, sprintf('[%s] %s', date("Y-m-d H:i:s"), $message) . PHP_EOL);
    }
}
