<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Illuminate\Config\Repository;

/**
 * @inheritdoc
 */
class CronConsumersRunner implements ProcessInterface
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
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

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
        $this->logger->info('Updating env.php cron consumers runner configuration.');
        $config = $this->configReader->read();
        $cronConsumersRunnerConfig = new Repository($this->environment->getCronConsumersRunner());

        $config['cron_consumers_runner'] = [
            'cron_run' => $cronConsumersRunnerConfig->get('cron_run') === 'true',
            'max_messages' => $cronConsumersRunnerConfig->get('max_messages', 10000),
            'consumers' => $cronConsumersRunnerConfig->get('consumers', []),
        ];

        $this->configWriter->write($config);
    }
}
