<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Http\TransferStatsHandler;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * Request configured URLs and record their time to first byte.
 */
class TimeToFirstByte implements StepInterface
{
    /**
     * @var PostDeployInterface
     */
    private $postDeploy;

    /**
     * @var PoolFactory
     */
    private $poolFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransferStatsHandler
     */
    private $statHandler;

    /**
     * @param PostDeployInterface $config
     * @param PoolFactory $poolFactory
     * @param UrlManager $urlManager
     * @param TransferStatsHandler $statHandler
     * @param LoggerInterface $logger
     */
    public function __construct(
        PostDeployInterface $config,
        PoolFactory $poolFactory,
        UrlManager $urlManager,
        TransferStatsHandler $statHandler,
        LoggerInterface $logger
    ) {
        $this->postDeploy = $config;
        $this->poolFactory = $poolFactory;
        $this->urlManager = $urlManager;
        $this->statHandler = $statHandler;
        $this->logger = $logger;
    }

    /**
     * Run time to first byte tests.
     *
     * @throws StepException
     */
    public function execute()
    {
        try {
            $pool = $this->poolFactory->create($this->getUrls(), [
                'options' => [RequestOptions::ON_STATS => $this->statHandler],
                'concurrency' => 1,
            ]);

            /** @var PromiseInterface $promise */
            $promise = $pool->promise();
            $promise->wait();
        } catch (\Throwable $e) {
            throw new StepException($e->getMessage(), Error::PD_DURING_TIME_TO_FIRST_BYTE, $e);
        }
    }

    /**
     * Returns list of URLs which should tested.
     *
     * @return array
     * @throws ConfigException
     */
    private function getUrls(): array
    {
        return array_filter(
            $this->postDeploy->get(PostDeployInterface::VAR_TTFB_TESTED_PAGES),
            function ($page) {
                if (!$this->urlManager->isUrlValid($page)) {
                    $this->logger->warning(sprintf('Will not test %s, host is not a configured store domain', $page));

                    return false;
                }

                return true;
            }
        );
    }
}
