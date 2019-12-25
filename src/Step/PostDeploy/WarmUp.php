<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\WarmUp\Urls;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUp implements StepInterface
{
    /**
     * @var PoolFactory
     */
    private $poolFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Urls
     */
    private $urls;

    /**
     * @param PoolFactory $poolFactory
     * @param LoggerInterface $logger
     * @param Urls $urls
     */
    public function __construct(
        PoolFactory $poolFactory,
        LoggerInterface $logger,
        Urls $urls
    ) {
        $this->poolFactory = $poolFactory;
        $this->logger = $logger;
        $this->urls = $urls;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        $this->logger->info('Starting page warming up');

        $urls = $this->urls->getAll();

        $fulfilled = function ($response, $index) use ($urls) {
            $this->logger->info('Warmed up page: ' . $urls[$index]);
        };

        $rejected = function (RequestException $exception, $index) use ($urls) {
            $context = [];

            if ($exception->getResponse()) {
                $context = [
                    'error' => $exception->getResponse()->getReasonPhrase(),
                    'code' => $exception->getResponse()->getStatusCode(),
                ];
            } elseif ($exception->getHandlerContext()) {
                $context = [
                    'error' => $exception->getHandlerContext()['error'] ?? '',
                    'errno' => $exception->getHandlerContext()['errno'] ?? '',
                    'total_time' => $exception->getHandlerContext()['total_time'] ?? '',
                ];
            }

            $this->logger->error('Warming up failed: ' . $urls[$index], $context);
        };

        try {
            $pool = $this->poolFactory->create($urls, compact('fulfilled', 'rejected'));

            /** @var PromiseInterface $promise */
            $promise = $pool->promise();
            $promise->wait();
        } catch (\Throwable $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
