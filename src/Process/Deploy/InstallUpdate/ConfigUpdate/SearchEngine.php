<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
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
     * @var Writer
     */
    private $writer;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param Writer $writer
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        Writer $writer,
        DeployInterface $stageConfig,
        MagentoVersion $version
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->writer = $writer;
        $this->stageConfig = $stageConfig;
        $this->magentoVersion = $version;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->logger->info('Skipping update to search engine configuration.');
            return;
        }
        
        $this->logger->info('Updating search engine configuration.');

        $searchConfig = $this->getSearchConfiguration();

        $this->logger->info('Set search engine to: ' . $searchConfig['engine']);
        $config['system']['default']['catalog']['search'] = $searchConfig;
        $this->writer->update($config);
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
        return [
            'engine' => 'elasticsearch',
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
