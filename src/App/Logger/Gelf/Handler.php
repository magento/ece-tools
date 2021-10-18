<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Gelf;

use Monolog\Handler\GelfHandler;

/**
 * Wrapper for GelfHandler class.
 */
class Handler extends GelfHandler
{
    /**
     * This method wraps parent method with try catch for avoiding stopping deploy process after
     * losing connection to graylog server.
     *
     * @param array $record
     * @codeCoverageIgnore
     */
    protected function write(array $record): void
    {
        try {
            parent::write($record);
        } catch (\RuntimeException $e) {
            fwrite(STDERR, 'Can\'t send log message: ' . $e->getMessage());
        }
    }
}
