<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * Max messages that will be processed by each consumer
     */
    const DEFAULT_MAX_MESSAGES = 10000;

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        MagentoVersion $version
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $version;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $version = $this->magentoVersion->getVersion();
            $this->logger->info(
                sprintf('Updating cron consumer runner is not supported in Magento %s, skipping.', $version)
            );
            return;
        }
        
        $this->logger->info('Updating env.php cron consumers runner configuration.');
        $config = $this->configReader->read();
        $runnerConfig = new Repository(
            $this->stageConfig->get(DeployInterface::VAR_CRON_CONSUMERS_RUNNER)
        );

        $config['cron_consumers_runner'] = [
            'cron_run' => $runnerConfig->get('cron_run') === 'true',
            'max_messages' => $runnerConfig->get('max_messages', static::DEFAULT_MAX_MESSAGES),
            'consumers' => $runnerConfig->get('consumers', []),
        ];

        $this->configWriter->write($config);
    }
}
