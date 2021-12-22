<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CronConsumersRunner implements StepInterface
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
     * @var RepositoryFactory
     */
    private $repositoryFactory;

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
     * @param MagentoVersion $version
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        MagentoVersion $version,
        RepositoryFactory $repositoryFactory
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $version;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
                return;
            }

            $this->logger->info('Updating env.php cron consumers runner configuration.');
            $config = $this->configReader->read();
            $runnerConfig = $this->repositoryFactory->create(
                $this->stageConfig->get(DeployInterface::VAR_CRON_CONSUMERS_RUNNER)
            );

            $config['cron_consumers_runner'] = [
                'cron_run' => $runnerConfig->get('cron_run') === true,
                'max_messages' => $runnerConfig->get('max_messages', static::DEFAULT_MAX_MESSAGES),
                'consumers' => $runnerConfig->get('consumers', []),
                'multiple_processes' => $runnerConfig->get('multiple_processes', []),
            ];
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->configWriter->create($config);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE, $e);
        }
    }
}
