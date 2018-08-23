<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Returns version of elasticsearch
 */
class ElasticSearch
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ClientFactory $clientFactory,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Returns version of elasticsearch service.
     * Returns 0 if response from elasticsearch doesn't contain version number or
     *   elasticsearch doesn't exist in relationships.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $relationships = $this->environment->getRelationships();
            if (!isset($relationships['elasticsearch'])) {
                return $this->version;
            }

            $esConfig = $relationships['elasticsearch'][0];

            try {
                $response = $this->clientFactory->create()->get(sprintf(
                    '%s:%s',
                    $esConfig['host'],
                    $esConfig['port']
                ));
                $esConfiguration = $response->getBody()->getContents();
                $esConfiguration = json_decode($esConfiguration, true);

                $this->version = $esConfiguration['version']['number'];
            } catch (\Exception $exception) {
                $this->logger->warning('Can\'t get version of elasticsearch: ' . $exception->getMessage());
            }
        }

        return $this->version;
    }
}
