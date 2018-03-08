<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
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
     * @var RequestFactory
     */
    private $requestFactory;

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
     * @param RequestFactory $requestFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        UrlManager $urlManager,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
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
            'index.php/customer/account/create',
        ];

        $client = $this->clientFactory->create();

        array_walk($pages, function ($page) use ($client) {
            $url = $this->urlManager->getDefaultSecureUrl() . $page;
            $request = $this->requestFactory->create('GET', $url);

            $client->sendAsync($request)->then(function () use ($url) {
                $this->logger->info('Warmed up page: ' . $url);
            }, function (RequestException $exception) use ($url) {
                $this->logger->error('Warming up failed' . $url, [
                    'error' => $exception->getResponse()->getReasonPhrase(),
                    'code' => $exception->getResponse()->getStatusCode(),
                ]);
            })->wait();
        });
    }
}
