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
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
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

    /** @var File */
    private $file;

    /** @var FileList */
    private $fileList;

    /** @var bool */
    private $lock = false;

    public function __construct(
        PostDeployInterface $config,
        PoolFactory $poolFactory,
        UrlManager $urlManager,
        LoggerInterface $logger,
        FileList $fileList,
        File $file
    ) {
        $this->postDeploy = $config;
        $this->poolFactory = $poolFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
        $this->fileList = $fileList;
        $this->file = $file;
    }

    public function execute()
    {
        if (!$this->postDeploy->get(PostDeployInterface::VAR_ENABLE_TTFB_TEST)) {
            $this->logger->debug('Time to first byte testing has been disabled.');
            return;
        }

        $requestOpts = [RequestOptions::ON_STATS => [$this, 'statHandler']];

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

    /**
     * Log stats from Guzzle request.
     *
     * @param TransferStats $stats
     */
    public function statHandler(TransferStats $stats)
    {
        $status = $stats->hasResponse() ? $stats->getResponse()->getStatusCode() : 'unknown';

        if (300 < $status && $status < 400) {
            $this->logger->debug('TTFB response was a redirect', ['url' => (string) $stats->getEffectiveUri()]);
            return;
        }

        if (!array_key_exists(CURLINFO_STARTTRANSFER_TIME, $stats->getHandlerStats())) {
            $this->logger->debug('cURL stats are missing from the request; using total transfer time');
            $time = $stats->getTransferTime();
        } else {
            $time = $stats->getHandlerStats()[CURLINFO_STARTTRANSFER_TIME];
        }

        $this->logger->info(
            sprintf('TTFB test result: %01.3fs', $time),
            ['url' => (string) $stats->getEffectiveUri(), 'status' => $status]
        );

        while ($this->lock);
        $this->lock = true;

        $historicData = $this->file->isExists($this->fileList->getTtfbLog())
            ? json_decode($this->file->fileGetContents($this->fileList->getTtfbLog()), true)
            : [];

        $historicData[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => (string) $stats->getEffectiveUri(),
            'status' => $status,
            'ttfb' => $time,
        ];

        $this->file->filePutContents($this->fileList->getTtfbLog(), json_encode($historicData, JSON_UNESCAPED_SLASHES));

        $this->lock = false;
    }
}
