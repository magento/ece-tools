<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

class CronInterval implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $config = $this->configReader->read();
        /* TODO: Currently, we are checking if it is production enviornment and assuming that if it is,
         * then it is 1 minute intervals.  If it is not, then we assume it is 5 minute intervals.
         * This assumption may not be correct because it can be manually changed by Platform.sh.
         * We have no way of determinining what the actual interval is, so this is the best we can do for now.
         */
        switch($this->environment->getEnvironmentType()) {
            case Environment::ENVIRONMENT_TYPE_PRODUCTION: {
                // Normally, production environments are set to 1 minute intervals.
                $this->logger->info('Updating env.php to remove cron_interval. (This is production environment.)');
                unset($config["system"]["default"]["cron"]["cron_interval"]);
                break;
            }
            default: {
                // Normally, non-production environments are set to 5 minute intervals.
                $this->logger->info('Updating env.php to have cron_interval 5. (This is not production environment.)');
                $config["system"]["default"]["cron"]["cron_interval"] = "5";
                break;
            }
        }
        $this->configWriter->write($config);
    }
}
