<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Formatter;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Magento\MagentoCloud\App\Logger\Formatter\ErrorFormatterFactory;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\App\Logger\Formatter\JsonErrorFormatter;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class ErrorFormatterFactoryTest extends TestCase
{
    /**
     * @var ErrorFormatterFactory
     */
    private $errorFormatterFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->createMock(ContainerInterface::class);

        $this->errorFormatterFactory = new ErrorFormatterFactory($this->containerMock);
    }

    public function testCreate()
    {
        $this->containerMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ErrorInfo::class],
                [ReaderInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ErrorInfo::class),
                $this->getMockForAbstractClass(ReaderInterface::class)
            );

        $errorFormatter = $this->errorFormatterFactory->create();
        $this->assertInstanceOf(JsonErrorFormatter::class, $errorFormatter);
    }
}
