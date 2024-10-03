<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Gelf;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\App\Logger\Gelf\MessageFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MessageFormatterTest extends TestCase
{
    /**
     * @var MessageFormatter
     */
    private $messageFormatter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->messageFormatter = new MessageFormatter();
    }

    public function testSetAdditional()
    {
        $this->messageFormatter->setAdditional([
            'some_key' => 'some_value'
        ]);

        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'some_channel',
                level: \Monolog\Level::Info,
                message: 'some message',
                context: []
            );
        } else {
            $logRecord = [
                'message' => 'some message',
                'datetime' => new \DateTime(),
                'level' => Logger::INFO,
                'extra' => [],
                'context' => [],
                'channel' => 'some_channel'
            ];
        }

        $message = $this->messageFormatter->format($logRecord);

        $this->assertEquals(
            [
                'some_key' => 'some_value',
                'facility' => 'some_channel'
            ],
            $message->getAllAdditionals()
        );
    }
}
