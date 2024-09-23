<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Processor;

use Magento\MagentoCloud\App\Logger\Sanitizer;

if (\Monolog\Logger::API == 3) {
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
         *
         * @param LogRecord $record
         * @return LogRecord
         */
        public function __invoke(\Monolog\LogRecord $record)
        {  
            $message = $this->sanitizer->sanitize($record->message);
            // Create new LogRecord from existing and update the message, 
            // since message is read only
            $record = new \Monolog\LogRecord(
                datetime: $record->datetime,
                channel: $record->channel,
                level: $record->level,
                message: $message,
                context: $record->context,
                extra: $record->extra,
            );

            return $record;
        }
    }
} else {
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
         *
         * @param array $record
         * @return array
         */
        public function __invoke(array $record)
        {
            $record['message'] = $this->sanitizer->sanitize($record['message']);

            return $record;
        }
    }
}
