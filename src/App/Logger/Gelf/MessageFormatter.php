<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Gelf;

use Monolog\Formatter\GelfMessageFormatter;
use Gelf\Message;

/**
 * Extends functionality of GelfMessageFormatter.
 * Adds possibility to set additional data for all messages that used current formatter.
 */
class MessageFormatter extends GelfMessageFormatter
{
    /**
     * @var array
     */
    private $additional;

    /**
     * Sets additional data that will be applied to all messages.
     *
     * @param array $additional
     */
    public function setAdditional(array $additional)
    {
        $this->additional = $additional;
    }

    /**
     * @inheritdoc
     */
    public function format(array $record): Message
    {
        $message = parent::format($record);

        foreach ($this->additional as $key => $value) {
            $message->setAdditional($key, $value);
        }

        return $message;
    }
}
