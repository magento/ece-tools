<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\RequestOptions;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Http\TransferStatsHandler;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * Request configured URLs and record their time to first byte.
 */
class TimeToFirstByte implements ProcessInterface
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

    /** @var TransferStatsHandler */
    private $statHandler;

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

    public function execute()
    {
        if (!$this->postDeploy->get(PostDeployInterface::VAR_ENABLE_TTFB_TEST)) {
            $this->logger->debug('Time to first byte testing has been disabled.');
            return;
        }

        $requestOpts = [RequestOptions::ON_STATS => $this->statHandler];

        try {
            $pool = $this->poolFactory->create($this->getUrlsForTesting(), [
                'options' => $requestOpts,
                'concurrency' => 1,
            ]);

            $pool->promise()->wait();
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns list of URLs which should tested.
     *
     * @return array
     */
    public function getUrlsForTesting(): array
    {
        return array_filter(
            $this->postDeploy->get(PostDeployInterface::VAR_TTFB_TESTED_PAGES),
            function ($page) {
                if (!$this->urlManager->isUrlValid($page)) {
                    $this->logger->error(sprintf('Will not test %s, host is not a configured store domain', $page));

                    return false;
                }

                return true;
            }
        );
    }
}
