<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App;

use Magento\MagentoCloud\App\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var ErrorHandler
     */
    private $handler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->handler = new ErrorHandler();
    }

    public function testHandleDatetime()
    {
        $this->assertFalse(
            $this->handler->handle(1, 'DateTimeZone::__construct', 'some_file.php', 1)
        );
    }

    public function testHandleNoError()
    {
        $this->assertFalse(
            $this->handler->handle(0, 'Some string', 'some_file.php', 1)
        );
    }

    public function testHandleWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error: Some string in some_file.php on line 1');

        $this->handler->handle(1, 'Some string', 'some_file.php', 1);
    }

    public function testHandleWithUnknownException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown error (3): Some string in some_file.php on line 1');
        $this->handler->handle(3, 'Some string', 'some_file.php', 1);
    }
}
