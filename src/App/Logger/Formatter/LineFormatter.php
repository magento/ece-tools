<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Formatter;

use Monolog\Logger;

/**
 * Formatter for log messages for cloud.log
 */
class LineFormatter extends \Monolog\Formatter\LineFormatter
{
    public const FORMAT_BASE = "[%datetime%] %level_name%: %message% %extra%\n";
    public const FORMAT_BASE_ERROR = "[%datetime%] %level_name%: [%context.errorCode%] %message% %extra%\n";

    /**
     * @param string $format The format of the message
     * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
     * @param bool $allowInlineLineBreaks Whether to allow inline line breaks in log entries
     * @param bool $ignoreEmptyContextAndExtra
     */
    public function __construct(
        $format = null,
        $dateFormat = null,
        $allowInlineLineBreaks = false,
        $ignoreEmptyContextAndExtra = false
    ) {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * @inheritDoc
     */
    public function format(array $record)
    {
        if (empty($record['message'])) {
            return false;
        }

        if ($record['level_name'] == Logger::getLevelName(Logger::WARNING)
            && !empty($record['context']['errorCode'])
        ) {
            $this->format = self::FORMAT_BASE_ERROR;
        } else {
            $this->format = self::FORMAT_BASE;
        }

        return parent::format($record);
    }
}
