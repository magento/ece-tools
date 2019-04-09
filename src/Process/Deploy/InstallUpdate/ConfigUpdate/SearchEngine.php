<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Process\ProcessException;
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
     * Returns search configuration
     *
     * @var SearchEngineConfig
     */
    private $searchEngineConfig;

    /**
     * @var ElasticSuite
     */
    private $elasticSuite;

    /**
     * @param LoggerInterface $logger
     * @param EnvWriter $envWriter
     * @param SharedWriter $sharedWriter
     * @param MagentoVersion $version
     * @param SearchEngineConfig $searchEngineConfig
     * @param ElasticSuite $elasticSuite
     */
    public function __construct(
        LoggerInterface $logger,
        EnvWriter $envWriter,
        SharedWriter $sharedWriter,
        MagentoVersion $version,
        SearchEngineConfig $searchEngineConfig,
        ElasticSuite $elasticSuite
    ) {
        $this->logger = $logger;
        $this->envWriter = $envWriter;
        $this->sharedWriter = $sharedWriter;
        $this->magentoVersion = $version;
        $this->searchEngineConfig = $searchEngineConfig;
        $this->elasticSuite = $elasticSuite;
    }

    /**
     * Executes the process.
     *
     * @return void
     * @throws ProcessException
     */
    public function execute()
    {
        $searchConfig = $this->searchEngineConfig->get();

        $this->logger->info('Updating search engine configuration.');
        $this->logger->info('Set search engine to: ' . $searchConfig['engine']);

        $config['system']['default']['catalog']['search'] = $searchConfig;

        if ($this->elasticSuite->isAvailable()) {
            $this->logger->info('Configuring ElasticSuite');

            $config['system']['default']['smile_elasticsuite_core_base_settings'] = $this->elasticSuite->get();
        } elseif ($this->elasticSuite->isInstalled()) {
            $this->logger->warning('ElasticSuite is installed without ElasticSearch');
        }

        try {
            $isMagento21 = $this->magentoVersion->satisfies('2.1.*');

            // 2.1.x requires search config to be written to the shared config file: MAGECLOUD-1317
            if ($isMagento21) {
                $this->sharedWriter->update($config);
            } else {
                $this->envWriter->update($config);
            }
        } catch (GenericException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
