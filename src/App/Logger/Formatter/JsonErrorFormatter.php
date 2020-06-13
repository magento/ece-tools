<?php

namespace Magento\MagentoCloud\App\Logger\Formatter;

use Magento\MagentoCloud\App\ErrorInfo;
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
     * @param int $batchMode
     * @param bool $appendNewline
     * @param ErrorInfo|null $errorInfo
     */
    public function __construct(
        $batchMode = self::BATCH_MODE_JSON,
        $appendNewline = true,
        ErrorInfo $errorInfo = null
    ) {
        parent::__construct($batchMode, $appendNewline);

        $this->errorInfo = $errorInfo;
    }

    /**
     * Format record, skip logging if ErrorCode isn't passed.
     *
     * {@inheritDoc}
     */
    public function format(array $record)
    {
        if (!isset($record['context']['errorCode'])) {
            return false;
        }

        return parent::format($this->formatLog($record));
    }

    /**
     * Returns error info data based on errorCode.
     *
     * @param array $record
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function formatLog(array $record): array
    {
        $errorCode = $record['context']['errorCode'];
        $errorInfo = $this->errorInfo->get($errorCode);

        if (empty($errorInfo)) {
            $errorInfo = [
                'errorCode' => $errorCode,
                'title' => $record['message']
            ];
        } else {
            $errorInfo['errorCode'] = $errorCode;
            if (!empty($record['message'])) {
                $errorInfo['title'] = $record['message'];
            }
        }

        ksort($errorInfo);
        return $errorInfo;
    }
}
