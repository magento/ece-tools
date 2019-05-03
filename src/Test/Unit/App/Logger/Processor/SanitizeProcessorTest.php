<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
        $this->assertEquals(['message' => 'sanitized message'], $sanitizeProcessor(['message' => 'some message']));
    }
}
