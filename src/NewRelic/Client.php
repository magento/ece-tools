<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\NewRelic;

use GuzzleHttp\RequestOptions;
use Magento\MagentoCloud\Http\ClientFactory;


class Client
{
    /**
     * @var string
     */
    private $deploymentUrl = 'https://api.newrelic.com/deployments.xml';

    /**
     * @var ClientFactory
     */
    private $httpClientFactory;
    /**
     * @var Config
     */
    private $config;

    /**
     * @param ClientFactory $httpClientFactory
     * @param Config $config
     */
    public function __construct(ClientFactory $httpClientFactory, Config $config)
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function registerDeployment(): bool
    {
        $apiKey = $this->config->getApiKey();
        $appName = $this->config->getAppName();

        if (empty($apiKey) || empty($appName)) {
            return false;
        }

        $options['app_name'] = $appName;
        //$options['user'] = $user;
        $options['revision'] = $this->config->getRevision();

        $client = $this->httpClientFactory->create();
        $result = $client->post(
            $this->deploymentUrl,
            [
                RequestOptions::HEADERS => [
                    'x-api-key' => $apiKey
                ],
                RequestOptions::JSON => [
                    'deployment' => $options
                ]
            ]
        );

        return true;
    }
}
