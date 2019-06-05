<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

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

    public function __construct(
        PostDeployInterface $config,
        PoolFactory $poolFactory,
        UrlManager $urlManager,
        LoggerInterface $logger
    ) {
        $this->postDeploy = $config;
        $this->poolFactory = $poolFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!$this->postDeploy->get(PostDeployInterface::VAR_ENABLE_TTFB_TEST)) {
            $this->logger->debug('Time to first byte testing has been disabled.');
            return;
        }

        $requestOpts = [
            RequestOptions::ON_STATS => function (TransferStats $stats) {
                if (!array_key_exists(CURLINFO_STARTTRANSFER_TIME, $stats->getHandlerStats())) {
                    $this->logger->debug('cURL stats are missing from the request; using total transfer time.');
                    $time = $stats->getTransferTime();
                } else {
                    $time = $stats->getHandlerStats()[CURLINFO_STARTTRANSFER_TIME];
                }

                $status = $stats->hasResponse() ? $stats->getResponse()->getStatusCode() : 'unknown';

                if (300 < $status && $status < 400) {
                    $this->logger->debug('TTFB response was a redirect');
                    return;
                }

                $this->logger->info(sprintf(
                    'TTFB test results: { url: %s, status: %s, time: %01.3f }',
                    $stats->getEffectiveUri(),
                    $status,
                    $time
                ));
            }
        ];

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
