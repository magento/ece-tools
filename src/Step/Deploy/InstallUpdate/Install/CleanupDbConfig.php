<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Cleans Magento database and resource configuration
 */
class CleanupDbConfig implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @param LoggerInterface $logger
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param DbConfig $dbConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        DbConfig $dbConfig
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->dbConfig = $dbConfig;
    }

    /**
     * Cleans Magento database and resource configurations in the case
     * when split database connections exist  and
     * the host of Magento default connection is different
     * from the default connection host of environment configuration
     *
     * @throws StepException
     */
    public function execute()
    {
        try {
            $envDbConfig = $this->dbConfig->get();
            $mageConfig = $this->configReader->read();
            $mageDbConfig = $mageConfig['db'] ?? [];
            $mageSplitDbConnectionsConfig = array_intersect_key(
                $mageDbConfig['connection'] ?? [],
                array_flip(DbConfig::SPLIT_CONNECTIONS)
            );

            $envDbConnectionDefaultHost = $envDbConfig['connection']['default']['host'] ?? '';
            $mageDbConnectionDefaultHost = $mageDbConfig['connection']['default']['host'] ?? '';

            if (!empty($mageSplitDbConnectionsConfig)
                && ($envDbConnectionDefaultHost !== $mageDbConnectionDefaultHost)) {
                $this->logger->notice(
                    'Previous split DB connection will be lost as new custom main connection was set'
                );
                // The 'install' key needs to be deleted, because otherwise,
                // during installation, Magento cannot use the new connections
                unset(
                    $mageConfig['install'],
                    $mageConfig['db'],
                    $mageConfig['resource']
                );

                $this->configWriter->create($mageConfig);
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
