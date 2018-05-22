<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SearchEngine implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EnvWriter
     */
    private $envWriter;

    /**
     * @var SharedWriter
     */
    private $sharedWriter;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param LoggerInterface $logger
     * @param EnvWriter $envWriter
     * @param SharedWriter $sharedWriter
     * @param MagentoVersion $version
     * @param Config $config
     */
    public function __construct(
        LoggerInterface $logger,
        EnvWriter $envWriter,
        SharedWriter $sharedWriter,
        MagentoVersion $version,
        Config $config
    ) {
        $this->logger = $logger;
        $this->envWriter = $envWriter;
        $this->sharedWriter = $sharedWriter;
        $this->magentoVersion = $version;
        $this->config = $config;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Updating search engine configuration.');

        $searchConfig = $this->config->get();

        $this->logger->info('Set search engine to: ' . $searchConfig['engine']);
        $config['system']['default']['catalog']['search'] = $searchConfig;

        // 2.1.x requires search config to be written to the shared config file: MAGECLOUD-1317
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->sharedWriter->update($config);

            return;
        }
        $this->envWriter->update($config);
    }
}
