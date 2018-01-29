<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Gelf;

use Monolog\Formatter\GelfMessageFormatter;

class MessageFormatter extends GelfMessageFormatter
{
    /**
     * @var array
     */
    private $additional;

    /**
     * @param array $additional
     */
    public function setAdditional(array $additional)
    {
        $this->additional = $additional;
    }

    /**
     * @inheritdoc
     */
    public function format(array $record)
    {
        $message = parent::format($record);

        foreach ($this->additional as $key => $value) {
            $message->setAdditional($key, $value);
        }

        return $message;
    }
}
