<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp\Urls;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Urls
     */
    private $urls;

    /**
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param LoggerInterface $logger
     * @param Urls $urls
     */
    public function __construct(
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        LoggerInterface $logger,
        Urls $urls
    ) {
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
        $this->urls = $urls;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function execute()
    {
        $client = $this->clientFactory->create();
        $promises = [];

        try {
            $this->logger->info('Starting page warming up');

            foreach ($this->urls->getAll() as $url) {
                $request = $this->requestFactory->create('GET', $url);

                $promises[] = $client->sendAsync($request)->then(function () use ($url) {
                    $this->logger->info('Warmed up page: ' . $url);
                }, function (RequestException $exception) use ($url) {
                    $context = [];
                    if ($exception->getResponse()) {
                        $context = [
                            'error' => $exception->getResponse()->getReasonPhrase(),
                            'code' => $exception->getResponse()->getStatusCode(),
                        ];
                    } else if ($exception->getHandlerContext()) {
                        $context = [
                            'error' => $exception->getHandlerContext()['error'] ?? '',
                            'errno' => $exception->getHandlerContext()['errno'] ?? '',
                            'total_time' => $exception->getHandlerContext()['total_time'] ?? ''
                        ];
                    }

                    $this->logger->error('Warming up failed: ' . $url, $context);
                });
            }

            \GuzzleHttp\Promise\unwrap($promises);
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
