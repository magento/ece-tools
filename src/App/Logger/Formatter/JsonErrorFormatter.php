<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Formatter;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Monolog\Formatter\JsonFormatter;

/**
 * Formatter for log messages for cloud.error.log
 */
class JsonErrorFormatter extends JsonFormatter
{
    /**
     * @var ErrorInfo
     */
    private $errorInfo;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @param ErrorInfo $errorInfo
     * @param ReaderInterface $reader
     * @param 1|2 $batchMode
     * @param bool $appendNewline
     */
    public function __construct(
        ErrorInfo $errorInfo,
        ReaderInterface $reader,
        $batchMode = self::BATCH_MODE_JSON,
        $appendNewline = true
    ) {
        parent::__construct($batchMode, $appendNewline);

        $this->errorInfo = $errorInfo;
        $this->reader = $reader;
    }

    /**
     * Format record, skip logging if ErrorCode isn't passed.
     *
     * {@inheritDoc}
     */
    public function format(array $record): string
    {
        try {
            if (!isset($record['context']['errorCode'])) {
                return '';
            }

            $loggedErrors = $this->reader->read();

            if (isset($loggedErrors[$record['context']['errorCode']])) {
                return '';
            }

            return parent::format($this->formatLog($record));
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Returns error info data based on errorCode.
     *
     * @param array $record
     * @return array
     * @throws FileSystemException
     */
    private function formatLog(array $record): array
    {
        $errorCode = $record['context']['errorCode'];
        $errorInfo = $this->errorInfo->get($errorCode);

        if (empty($errorInfo)) {
            $errorInfo = [
                'errorCode' => $errorCode,
                'title' => $record['message'] ?? ''
            ];
        } else {
            $errorInfo['errorCode'] = $errorCode;
            if (!empty($record['message'])) {
                $errorInfo['title'] = $record['message'];
            }
        }

        if (!empty($record['context']['suggestion'])) {
            $errorInfo['suggestion'] = $record['context']['suggestion'];
        }

        ksort($errorInfo);

        return $errorInfo;
    }
}
