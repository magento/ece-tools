<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\Config;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 */
class DbConnection implements ProcessInterface
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
     * @var Config
     */
    private $dbConfig;

    /**
     * @param Config $dbConfig
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $dbConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        LoggerInterface $logger
    ) {
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->logger = $logger;
        $this->dbConfig = $dbConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $config = $this->configReader->read();

        $this->logger->info('Updating env.php DB connection configuration.');
        $config['db'] = $this->dbConfig->get();
        $config['resource'] = [
            'default_setup' => [
                'connection' => 'default',
            ],
        ];

        $this->configWriter->create($config);
    }
}
