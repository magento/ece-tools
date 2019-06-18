<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Http\PoolFactory;
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
            } else if ($exception->getHandlerContext()) {
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

            $pool->promise()->wait();
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
