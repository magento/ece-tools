<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;

/**
 * Processor for creation database dump
 */
class DumpProcessor
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
     * Uses for enabling and disabling magento maintenance mode on deploy phase
     *
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * Enables/disables Magento crons
     *
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * Kills all running Magento cron and consumers processes
     *
     * @var BackgroundProcess
     */
    private $backgroundProcess;

    /**
     * Creates database dump and archives it
     *
     * @var DumpGenerator
     */
    private $dumpGenerator;

    /**
     * Factory for creation database data connection classes
     *
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * Unlocks cron jobs stacked in status 'running'.
     *
     * @var JobUnlocker
     */
    private $jobUnlocker;

    /**
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     * @param Switcher $cronSwitcher
     * @param BackgroundProcess $backgroundProcess
     * @param DumpGenerator $dumpGenerator
     * @param ConnectionFactory $connectionFactory
     * @param JobUnlocker $jobUnlocker
     */
    public function __construct(
        MaintenanceModeSwitcher $maintenanceModeSwitcher,
        Switcher $cronSwitcher,
        BackgroundProcess $backgroundProcess,
        DumpGenerator $dumpGenerator,
        ConnectionFactory $connectionFactory,
        JobUnlocker $jobUnlocker
    ) {
        $this->maintenanceModeSwitcher = $maintenanceModeSwitcher;
        $this->cronSwitcher = $cronSwitcher;
        $this->backgroundProcess = $backgroundProcess;
        $this->dumpGenerator = $dumpGenerator;
        $this->connectionFactory = $connectionFactory;
        $this->jobUnlocker = $jobUnlocker;
    }

    /**
     * Executes creation databases dumps
     *
     * @param array $connections
     * @param bool $removeDefiners
     * @throws ConfigException
     * @throws FileSystemException
     * @throws GenericException
     */
    public function execute(array $connections, bool $removeDefiners)
    {
        try {
            $this->maintenanceModeSwitcher->enable();
            $this->cronSwitcher->disable();
            $this->backgroundProcess->kill();

            foreach ($connections as $connection) {
                $this->dumpGenerator->create(
                    self::DATABASE_MAP[$connection],
                    $this->connectionFactory->create($connection),
                    $removeDefiners
                );
            }
        } finally {
            $this->jobUnlocker->unlockAll('The job is terminated due to database dump');
            $this->cronSwitcher->enable();
            $this->maintenanceModeSwitcher->disable();
        }
    }
}
