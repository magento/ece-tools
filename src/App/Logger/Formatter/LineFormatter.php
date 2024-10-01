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
    public const FORMAT_BASE = "[%datetime%] %level_name%: %message%\n";
    public const FORMAT_BASE_ERROR = "[%datetime%] %level_name%: [%context.errorCode%] %message%\n";

    public function format(\Monolog\LogRecord|array $record): string
    {
        $errorLevels = [
            Logger::getLevelName(Logger::WARNING),
            Logger::getLevelName(Logger::ERROR),
            Logger::getLevelName(Logger::CRITICAL),
        ];
        // Monolog version 3 or higher.
        if ($record instanceof \Monolog\LogRecord) {
            if (isset($record->level->name)
                && in_array(strtoupper($record->level->name), $errorLevels)
                && !empty($record->context['errorCode'])
            ) {
                $this->format = self::FORMAT_BASE_ERROR;
            } else {
                $this->format = self::FORMAT_BASE;
            }

            if (isset($record->message) && !empty($record->context['suggestion'])) {
                // Create new LogRecord from existing and update the message,
                // since message is read only
                $message = $record->message . PHP_EOL . $record->context['suggestion'];
                $record = new \Monolog\LogRecord(
                    datetime: $record->datetime,
                    channel: $record->channel,
                    level: $record->level,
                    message: $message,
                    context: $record->context,
                    extra: $record->extra,
                );
            }
        } else if (is_array($record)) { // Older Monolog versions.
            if (isset($record['level_name'])
                && in_array($record['level_name'], $errorLevels)
                && !empty($record['context']['errorCode'])
            ) {
                $this->format = self::FORMAT_BASE_ERROR;
            } else {
                $this->format = self::FORMAT_BASE;
            }

            if (isset($record['message']) && !empty($record['context']['suggestion'])) {
                $record['message'] .= PHP_EOL . $record['context']['suggestion'];
            }
        }

        return parent::format($record);
    }
}
