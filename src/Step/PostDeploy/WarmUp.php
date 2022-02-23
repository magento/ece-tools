<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
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
     * @var PostDeployInterface
     */
    private $postDeploy;

    /**
     * @param PoolFactory $poolFactory
     * @param LoggerInterface $logger
     * @param Urls $urls
     * @param PostDeployInterface $postDeploy
     */
    public function __construct(
        PoolFactory $poolFactory,
        LoggerInterface $logger,
        Urls $urls,
        PostDeployInterface $postDeploy
    ) {
        $this->poolFactory = $poolFactory;
        $this->logger = $logger;
        $this->urls = $urls;
        $this->postDeploy = $postDeploy;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        try {
            $this->logger->info('Starting page warmup');
            $config = [];

            $concurrency = $this->postDeploy->get(PostDeployInterface::VAR_WARM_UP_CONCURRENCY);
            if ($concurrency) {
                $config['concurrency'] = $concurrency;
                $this->logger->info(
                    sprintf(
                        'Warmup concurrency set to %s as specified by the %s configuration',
                        $concurrency,
                        PostDeployInterface::VAR_WARM_UP_CONCURRENCY
                    )
                );
            }

            $urls = $this->urls->getAll();

            $config['fulfilled'] = function ($response, $index) use ($urls) {
                $this->logger->info('Warmed up page: ' . $urls[$index]);
            };

            $config['rejected'] = function ($exception, $index) use ($urls) {
                $context = [];

                if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
                    $context = [
                        'error' => $exception->getResponse()->getReasonPhrase(),
                        'code' => $exception->getResponse()->getStatusCode(),
                    ];
                } elseif (method_exists($exception, 'getHandlerContext') && $exception->getHandlerContext()) {
                    $context = [
                        'error' => $exception->getHandlerContext()['error'] ?? '',
                        'errno' => $exception->getHandlerContext()['errno'] ?? '',
                        'total_time' => $exception->getHandlerContext()['total_time'] ?? '',
                    ];
                }

                $this->logger->error('Warming up failed: ' . $urls[$index], $context);
            };

            $pool = $this->poolFactory->create($urls, $config);

            /** @var PromiseInterface $promise */
            $promise = $pool->promise();
            $promise->wait();
        } catch (\Throwable $e) {
            throw new StepException($e->getMessage(), Error::PD_DURING_PAGE_WARM_UP, $e);
        }
    }
}
