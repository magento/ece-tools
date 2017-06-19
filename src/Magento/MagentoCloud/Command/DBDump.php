<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;

class DBDump
{
    /**
     * @var Environment
     */
    private $env;

    public function __construct()
    {
        $this->env = new Environment();
    }

    /**
     * Create a dump of the database safely
     */
    public function execute()
    {
        $relationships = $this->env->getRelationships();

        $dbHost = $relationships["database"][0]["host"];
        $dbName = $relationships["database"][0]["path"];
        $dbUser = $relationships["database"][0]["username"];
        $dbPassword = $relationships["database"][0]["password"];
        $dbPort = '3307';

        $db = new \PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword);
        $current_active = $db->query("SELECT @@hostname;")->fetchColumn();
        $this_host = gethostname();
        $alternate_ip = preg_replace('~\.5\.5$~', '.6.6', gethostbyname($this_host));
        $alternate_host = gethostbyaddr($alternate_ip);


        if ($current_active == $this_host) {
            // Use a different DB host.
            echo "Using host #2 ($alternate_host) for database dump as #1 is the active master..." . PHP_EOL;
            $dbHost = $alternate_host;
        }
        else {
            echo "Using host #1 ($this_host) for database dump..." . PHP_EOL;
            $dbHost = '127.0.0.1';
        }

        putenv("LNX_PATH=$dbName");
        putenv("LNX_PORT=$dbPort");
        putenv("LNX_PASS=$dbPassword");
        putenv("LNX_HOST=$dbHost");
        putenv("LNX_USER=$dbUser");
        chdir(__DIR__);

        $this->env->log('Beginning production db dump.');

        # Lock the production sql dump so staging sync doesn't start using it until we're done.
        $this->env->log('Waiting for lock on prod db dump...');
        $lockReturnValue = $this->env->execute('flock -w 3600 9 || exit 1');

        $this->env->log('Got it.');
        $this->env->log('Starting dump...');

        # The actual dump script.
        $pipeStatus = $this->env->execute("timeout 3600 mysqldump -h {$dbHost} -P {$dbPort} -p{$dbPassword} -u {$dbUser} $dbName --single-transaction --no-autocommit --quick | gzip > /tmp/prod-attempt.sql.gz");

        # Eventually we want to use a Magento-supplied export script, which currently has too much locking and is not usable.
        # vendor/bin/m2-ece-db-sanitize > $TMPDIR/prod-attempt.sql.gz

        if($pipeStatus[0] === 0){
            $this->env->log('Done');
            $this->env->log('Success, renaming dump to final location.');
            $this->env->execute('mv $TMPDIR/prod-attempt.sql.gz $TMPDIR/prod-latest.sql.gz');
        } else {
            $this->env->log('Done.');
            $this->env->log('Failed.');
        }
        $this->env->log('Finished production db dump.');
    }
}