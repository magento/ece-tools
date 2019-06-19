<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Http;

use GuzzleHttp\TransferStats;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Psr\Log\LoggerInterface;

/**
 * Callback for Guzzle TransferStats to log their time to first byte value.
 */
class TransferStatsHandler
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param File $file
     * @param FileList $fileList
     * @param LoggerInterface $logger
     */
    public function __construct(File $file, FileList $fileList, LoggerInterface $logger)
    {
        $this->file = $file;
        $this->fileList = $fileList;
        $this->logger = $logger;
    }

    /**
     * Log stats from Guzzle request.
     *
     * @param TransferStats $stats
     */
    public function __invoke(TransferStats $stats)
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
    }
}
