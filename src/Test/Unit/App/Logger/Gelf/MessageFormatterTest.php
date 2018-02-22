<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
    protected function setUp()
    {
        $this->messageFormatter = new MessageFormatter();
    }

    public function testSetAdditional()
    {
        $this->messageFormatter->setAdditional([
            'some_key' => 'some_value'
        ]);

        $message = $this->messageFormatter->format([
            'message' => 'some message',
            'datetime' => new \DateTime(),
            'level' => Logger::INFO,
            'extra' => [],
            'context' => []
        ]);

        $this->assertEquals(
            [
                'some_key' => 'some_value'
            ],
            $message->getAllAdditionals()
        );
    }
}
