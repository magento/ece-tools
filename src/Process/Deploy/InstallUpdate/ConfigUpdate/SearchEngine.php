<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Deploy\Reader as EnvReader;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Config\Shared\Reader as SharedReader;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
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
     * @var EnvReader;
     */
    private $envReader;

    /**
     * @var SharedWriter
     */
    private $sharedWriter;

    /**
     * @var SharedReader
     */
    private $sharedReader;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * Returns search configuration
     *
     * @var SearchEngineConfig
     */
    private $searchEngineConfig;

    /**
     * @param LoggerInterface $logger
     * @param EnvWriter $envWriter
     * @param EnvReader $envReader
     * @param SharedWriter $sharedWriter
     * @param SharedReader $sharedReader
     * @param MagentoVersion $version
     * @param SearchEngineConfig $searchEngineConfig
     */
    public function __construct(
        LoggerInterface $logger,
        EnvWriter $envWriter,
        EnvReader $envReader,
        SharedWriter $sharedWriter,
        SharedReader $sharedReader,
        MagentoVersion $version,
        SearchEngineConfig $searchEngineConfig
    ) {
        $this->logger = $logger;
        $this->envWriter = $envWriter;
        $this->envReader = $envReader;
        $this->sharedWriter = $sharedWriter;
        $this->sharedReader = $sharedReader;
        $this->magentoVersion = $version;
        $this->searchEngineConfig = $searchEngineConfig;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Updating search engine configuration.');

        $searchConfig = $this->searchEngineConfig->get();

        $this->logger->info('Set search engine to: ' . $searchConfig['engine']);

        // 2.1.x requires search config to be written to the shared config file: MAGECLOUD-1317
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $config = $this->sharedReader->read();
            $config['system']['default']['catalog']['search'] = $searchConfig;
            $this->sharedWriter->create($config);

            return;
        }

        $config = $this->envReader->read();
        $config['system']['default']['catalog']['search'] = $searchConfig;
        $this->envWriter->create($config);
    }
}
