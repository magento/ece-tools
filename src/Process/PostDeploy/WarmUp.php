<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUp implements ProcessInterface
{
    /**
     * @var PostDeployInterface
     */
    private $postDeploy;

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
     * @param PostDeployInterface $postDeploy
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        PostDeployInterface $postDeploy,
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        UrlManager $urlManager,
        LoggerInterface $logger
    ) {
        $this->postDeploy = $postDeploy;
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $pages = $this->postDeploy->get(PostDeployInterface::VAR_WARM_UP_PAGES);
        $client = $this->clientFactory->create();
        $promises = [];

        try {
            $baseUrl = rtrim($this->urlManager->getBaseUrl(), '/');

            foreach ($pages as $page) {
                $url = $baseUrl . '/' . $page;
                $request = $this->requestFactory->create('GET', $url);

                $promises[] = $client->sendAsync($request)->then(function () use ($url) {
                    $this->logger->info('Warmed up page: ' . $url);
                }, function (RequestException $exception) use ($url) {
                    $context = $exception->getResponse()
                        ? [
                            'error' => $exception->getResponse()->getReasonPhrase(),
                            'code' => $exception->getResponse()->getStatusCode(),
                        ]
                        : [];

                    $this->logger->error('Warming up failed: ' . $url, $context);
                });
            }

            \GuzzleHttp\Promise\unwrap($promises);
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
