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
use Magento\MagentoCloud\Package\UndefinedPackageException;
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
    private const DATABASE_MAIN = 'main';
    private const DATABASE_QUOTE = 'quote';
    private const DATABASE_SALES = 'sales';

    /**
     * Connection map:
     * {connection name from environment} => {magento connection name}
     */
    private const CONNECTION_MAP = [
        ConnectionFactory::CONNECTION_SLAVE => DbConfig::CONNECTION_DEFAULT,
        ConnectionFactory::CONNECTION_QUOTE_SLAVE => DbConfig::CONNECTION_CHECKOUT,
        ConnectionFactory::CONNECTION_SALES_SLAVE => DbConfig::CONNECTION_SALES,
    ];

    /**
     * Database name map:
     * {connection name from environment} => {database name}
     */
    private const DATABASE_MAP = [
        ConnectionFactory::CONNECTION_SLAVE => self::DATABASE_MAIN,
        ConnectionFactory::CONNECTION_QUOTE_SLAVE => self::DATABASE_QUOTE,
        ConnectionFactory::CONNECTION_SALES_SLAVE => self::DATABASE_SALES,
    ];

    /**
     * Databases
     */
    const DATABASES = [
        self::DATABASE_MAIN,
        self::DATABASE_QUOTE,
        self::DATABASE_SALES,
    ];

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;

    /**
     * @var DumpGenerator
     */
    private $dumpGenerator;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var JobUnlocker
     */
    private $jobUnlocker;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     * @param Switcher $cronSwitcher
     * @param BackgroundProcess $backgroundProcess
     * @param DumpGenerator $dumpGenerator
     * @param ConnectionFactory $connectionFactory
     * @param JobUnlocker $jobUnlocker
     * @param DbConfig $dbConfig
     */
    public function __construct(
        MaintenanceModeSwitcher $maintenanceModeSwitcher,
        Switcher $cronSwitcher,
        BackgroundProcess $backgroundProcess,
        DumpGenerator $dumpGenerator,
        ConnectionFactory $connectionFactory,
        JobUnlocker $jobUnlocker,
        DbConfig $dbConfig
    ) {
        $this->maintenanceModeSwitcher = $maintenanceModeSwitcher;
        $this->cronSwitcher = $cronSwitcher;
        $this->backgroundProcess = $backgroundProcess;
        $this->dumpGenerator = $dumpGenerator;
        $this->connectionFactory = $connectionFactory;
        $this->jobUnlocker = $jobUnlocker;
        $this->dbConfig = $dbConfig;
    }

    /**
     * Executes creation databases dumps
     *
     * @param array $databases
     * @param bool $removeDefiners
     * @param string $alternativeDestination
     *
     * @throws ConfigException
     * @throws FileSystemException
     * @throws GenericException
     * @throws UndefinedPackageException
     */
    public function execute(bool $removeDefiners, array $databases = [], string $alternativeDestination = '')
    {
        try {
            if (empty($databases)) {
                $connections = array_values(array_intersect_key(
                    array_flip(self::CONNECTION_MAP),
                    $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? []
                ));
            } else {
                $connections = array_keys(array_intersect(self::DATABASE_MAP, $databases));
                $this->checkConnectionsAvailability($connections);
            }

            if (empty($connections)) {
                throw new GenericException('Database configuration does not exist');
            }

            $this->maintenanceModeSwitcher->enable();
            $this->cronSwitcher->disable();
            $this->backgroundProcess->kill();

            foreach ($connections as $connection) {
                $this->dumpGenerator->create(
                    self::DATABASE_MAP[$connection],
                    $this->connectionFactory->create($connection),
                    $removeDefiners,
                    $alternativeDestination
                );
            }
        } finally {
            $this->jobUnlocker->unlockAll('The job is terminated due to database dump');
            $this->cronSwitcher->enable();
            $this->maintenanceModeSwitcher->disable();
        }
    }

    /**
     * Checks availability of connections
     *
     * @param array $connections
     * @throws ConfigException
     * @throws GenericException
     */
    private function checkConnectionsAvailability(array $connections)
    {
        $envConnections = $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? [];
        foreach ($connections as $connection) {
            if (!isset($envConnections[self::CONNECTION_MAP[$connection]])) {
                throw new GenericException(sprintf(
                    'Environment does not have connection `%s` associated with database `%s`',
                    self::CONNECTION_MAP[$connection],
                    self::DATABASE_MAP[$connection]
                ));
            }
        }
    }
}
