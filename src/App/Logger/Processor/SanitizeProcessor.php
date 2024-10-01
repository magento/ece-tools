<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Processor;

use Magento\MagentoCloud\App\Logger\Sanitizer;

/**
 * Logger processor for sanitizing sensitive data.
 */
class SanitizeProcessor
{
    /**
     * @var Sanitizer
     */
    private $sanitizer;

    /**
     * @param Sanitizer $sanitizer
     */
    public function __construct(Sanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Finds and replace sensitive data in record message.
     */
    public function __invoke(\Monolog\LogRecord|array $record)
    {
        // Monolog version 3 or higher.
        if (\Monolog\Logger::API == 3) {
            $message = $this->sanitizer->sanitize($record->message); // @phpstan-ignore-line
            // Create new LogRecord from existing and update the message,
            // since message is read only
            $record = new \Monolog\LogRecord( // @phpstan-ignore-line
                datetime: $record->datetime, // @phpstan-ignore-line
                channel: $record->channel, // @phpstan-ignore-line
                level: $record->level, // @phpstan-ignore-line
                message: $message,
                context: $record->context, // @phpstan-ignore-line
                extra: $record->extra, // @phpstan-ignore-line
            );
            return $record;
        } else { // Older Monolog versions.
            $record['message'] = $this->sanitizer->sanitize($record['message']);
            return $record;
        }
    }
}
