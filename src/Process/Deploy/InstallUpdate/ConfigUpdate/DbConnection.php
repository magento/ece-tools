<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Database\ConfigInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
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
     * @var ConfigInterface
     */
    private $dbConfig;

    /**
     * @param ConfigInterface $dbConfig
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigInterface $dbConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        LoggerInterface $logger
    ) {
        $this->dbConfig = $dbConfig;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->logger = $logger;
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
