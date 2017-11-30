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
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
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
        $minLevel = 1;
        $maxLevel = 9;

        // Create the list of parameters to be expected on each invocation.
        $parameters = function () use ($minLevel, $maxLevel) {
            $runningArray = [];
            for ($i = $minLevel; $i <= $maxLevel; $i++) {
                $runningArray[] = [
                    $this->logicalAnd(
                        $this->stringContains('gzip -q --keep'),
                        $this->stringContains('xargs'),
                        $this->stringContains($this->staticContentCompressor::TARGET_DIR),
                        $this->stringContains("-$i")
                    ),
                ];
            }

            return $runningArray;
        };

        $this->shellMock
            ->expects($this->exactly(1 + $maxLevel - $minLevel))
            ->method('execute')
            ->withConsecutive(...$parameters());

        for ($i = $minLevel; $i <= $maxLevel; $i++) {
            $this->staticContentCompressor->process($i);
        }
    }

    public function testCompressionDisabled()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content compression was disabled.');

        $this->staticContentCompressor->process(0);
    }
}
