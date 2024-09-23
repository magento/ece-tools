<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Processor;

use Magento\MagentoCloud\App\Logger\Processor\SanitizeProcessor;
use Magento\MagentoCloud\App\Logger\Sanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SanitizeProcessorTest extends TestCase
{
    public function testInvoke()
    {
        /** @var Sanitizer|MockObject $sanitizerMock */
        $sanitizerMock = $this->createMock(Sanitizer::class);
        $sanitizerMock->expects($this->once())
            ->method('sanitize')
            ->with('some message')
            ->willReturn('sanitized message');

        $sanitizeProcessor = new SanitizeProcessor($sanitizerMock);
        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: \DateTimeImmutable::createFromFormat('j-M-Y', '15-Feb-2009'),
                channel: 'testChannel',
                level: \Monolog\Level::Info,
                message: 'some message',
                context: []
            );
            $sanitizedRecord = new \Monolog\LogRecord(
                datetime: \DateTimeImmutable::createFromFormat('j-M-Y', '15-Feb-2009'),
                channel: 'testChannel',
                level: \Monolog\Level::Info,
                message: 'sanitized message',
                context: []
            );
            $this->assertEquals($sanitizedRecord, $sanitizeProcessor($logRecord));
        } else {
            $logRecord = [
                'message' => 'some message',
            ];
            $this->assertEquals(['message' => 'sanitized message'], $sanitizeProcessor($logRecord));
        }
    }
}
