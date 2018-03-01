<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ScdOnDemand implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @param LoggerInterface $logger
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param GlobalConfig $globalConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        GlobalConfig $globalConfig
    ) {
        $this->logger = $logger;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php SCD on demand in production.');

        $config = [
            'static_content_on_demand_in_production' =>
                $this->globalConfig->get(GlobalConfig::VAR_SCD_ON_DEMAND) ? 1 : 0
        ];
        $this->configWriter->update($config);
    }
}
