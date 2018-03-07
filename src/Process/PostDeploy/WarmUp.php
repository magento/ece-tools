<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUp implements ProcessInterface
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     */
    public function __construct(ClientFactory $clientFactory, UrlManager $urlManager, LoggerInterface $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute()
    {
        $pages = [
            'index.php',
        ];

        array_walk($pages, function ($page) {
            $url = $this->urlManager->getDefaultSecureUrl() . '/' . $page;
            $client = $this->clientFactory->create();

            $response = $client->request(
                'GET',
                $url
            );

            $this->logger->info('Warming up page: ' . $url, [
                'code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
            ]);
        });
    }
}
