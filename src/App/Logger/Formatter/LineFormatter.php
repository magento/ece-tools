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

        if ($record instanceof \Monolog\LogRecord) {
            $record = $this->formatNew($record, $errorLevels);
        } else { // Older Monolog versions.
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

    private function formatNew(\Monolog\LogRecord $record, array $errorLevels)
    {
        if (isset($record->level->name) // @phpstan-ignore-line
            && in_array(strtoupper($record->level->name), $errorLevels)
            && !empty($record->context['errorCode']) // @phpstan-ignore-line
        ) {
            $this->format = self::FORMAT_BASE_ERROR;
        } else {
            $this->format = self::FORMAT_BASE;
        }

        if (isset($record->message) && !empty($record->context['suggestion'])) { // @phpstan-ignore-line
            // Create new LogRecord from existing and update the message,
            // since message is read only
            $message = $record->message . PHP_EOL . $record->context['suggestion'];
            $record = new \Monolog\LogRecord( // @phpstan-ignore-line
                datetime: $record->datetime, // @phpstan-ignore-line
                channel: $record->channel, // @phpstan-ignore-line
                level: $record->level, // @phpstan-ignore-line
                message: $message,
                context: $record->context,
                extra: $record->extra, // @phpstan-ignore-line
            );
        }
        return $record;
    }
}
