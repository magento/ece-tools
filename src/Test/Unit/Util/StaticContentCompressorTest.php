<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Unit test for static content compression.
 *
 * @package Magento\MagentoCloud\Test\Unit\Util
 */
class StaticContentCompressorTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock  = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        $this->staticContentCompressor = new StaticContentCompressor(
            $this->loggerMock,
            $this->shellMock
        );
    }

    /**
     * Test the method that compresses the files.
     */
    public function testCompression()
    {
        // Ensure that the shell object is receiving the proper argument.
        $this->shellMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('gzip --keep'),
                    $this->stringContains('xargs'),
                    $this->stringContains($this->staticContentCompressor::TARGET_DIR)
                )
            );

        $this->staticContentCompressor->compressStaticContent();
    }
}
