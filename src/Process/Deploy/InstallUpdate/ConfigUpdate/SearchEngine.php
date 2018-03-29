<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SearchEngine implements ProcessInterface
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
     * @var EnvWriter
     */
    private $envWriter;

    /**
     * @var SharedWriter
     */
    private $sharedWriter;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param EnvWriter $envWriter
     * @param SharedWriter $sharedWriter
     * @param DeployInterface $stageConfig
     * @param MagentoVersion $version
     * @param ClientFactory $client
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        EnvWriter $envWriter,
        SharedWriter $sharedWriter,
        DeployInterface $stageConfig,
        MagentoVersion $version,
        ClientFactory $client
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->envWriter = $envWriter;
        $this->sharedWriter = $sharedWriter;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $version;
        $this->clientFactory = $client;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Updating search engine configuration.');

        $searchConfig = $this->getSearchConfiguration();

        $this->logger->info('Set search engine to: ' . $searchConfig['engine']);
        $config['system']['default']['catalog']['search'] = $searchConfig;

        // 2.1.x requires search config to be written to the shared config file: MAGECLOUD-1317
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->sharedWriter->update($config);

            return;
        }
        $this->envWriter->update($config);
    }

    /**
     * @return array
     */
    private function getSearchConfiguration(): array
    {
        $envSearchConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);
        if ($this->isSearchConfigurationValid($envSearchConfiguration)) {
            return $envSearchConfiguration;
        }

        $relationships = $this->environment->getRelationships();

        if (isset($relationships['elasticsearch'])) {
            $searchConfig = $this->getElasticSearchConfiguration($relationships['elasticsearch'][0]);
        } elseif (isset($relationships['solr'])) {
            $searchConfig = $this->getSolrConfiguration($relationships['solr'][0]);
        } else {
            $searchConfig = ['engine' => 'mysql'];
        }

        return $searchConfig;
    }

    /**
     * Returns SOLR configuration
     *
     * @param array $config Solr connection configuration
     * @return array
     */
    private function getSolrConfiguration(array $config)
    {
        return [
            'engine' => 'solr',
            'solr_server_hostname' => $config['host'],
            'solr_server_port' => $config['port'],
            'solr_server_username' => $config['scheme'],
            'solr_server_path' => $config['path'],
        ];
    }

    /**
     * Returns ElasticSearch configuration
     *
     * @param array $config Elasticsearch connection configuration
     * @return array
     */
    private function getElasticSearchConfiguration(array $config)
    {
        $response = $this->clientFactory->create()->get(sprintf(
            '%s:%s',
            $config['host'],
            $config['port']
        ));
        $esConfiguration = $response->getBody()->getContents();
        $esConfiguration = json_decode($esConfiguration, true);
        $engine = $esConfiguration['version']['number'] >= 5 ? 'elasticsearch5' : 'elasticsearch';

        return [
            'engine' => $engine,
            'elasticsearch_server_hostname' => $config['host'],
            'elasticsearch_server_port' => $config['port'],
        ];
    }

    /**
     * Checks that given configuration is valid.
     *
     * @param array $searchConfiguration
     * @return bool
     */
    private function isSearchConfigurationValid(array $searchConfiguration): bool
    {
        return !empty($searchConfiguration) && isset($searchConfiguration['engine']);
    }
}
