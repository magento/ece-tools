<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

class SearchEngine implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Environment $environment,
        Adapter $adapter,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Updating SOLR configuration.');

        $solrConfig = $this->environment->getRelationship('solr');
        if (count($solrConfig)) {
            $updateQuery = "update core_config_data set value = '%s' where path = '%s' and scope_id = '0';";
            $updateConfig = [
                'catalog/search/solr_server_hostname' => $solrConfig[0]['host'],
                'catalog/search/solr_server_port' => $solrConfig[0]['port'],
                'catalog/search/solr_server_username' => $solrConfig[0]['scheme'],
                'catalog/search/solr_server_path' => $solrConfig[0]['path'],
            ];

            foreach ($updateConfig as $configPath => $value) {
                $this->adapter->execute(sprintf($updateQuery, $value, $configPath));
            }
        }
    }
}
