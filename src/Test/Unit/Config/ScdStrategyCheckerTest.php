<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use \Magento\MagentoCloud\Config\ScdStrategyChecker;
use \Magento\MagentoCloud\App\Logger;
use \Magento\MagentoCloud\Package\MagentoVersion;
use \PHPUnit\Framework\TestCase;
use \PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Class ScdStrategyCheckerTest
 *
 * @package Magento\MagentoCloud\Test\Unit\Config
 */
class ScdStrategyCheckerTest extends TestCase
{
    /**
     * @var Logger|Mock
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ScdStrategyChecker
     */
    private $scdStrategyChecker;

    /**
     *
     */
    protected function setUp()
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->scdStrategyChecker = new ScdStrategyChecker(
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    public function testFallbackOffset()
    {
        $this->assertEquals(
            0,
            $this->scdStrategyChecker::FALLBACK_OFFSET
        );
    }

    /**
     *
     */
    public function testGetStrategyTrivial()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method('warning');

        $this->assertEquals(
            'strategy',
            $this->scdStrategyChecker->getStrategy('strategy', ['strategy'])
        );
        $this->assertEquals(
            'strategy',
            $this->scdStrategyChecker->getStrategy('strategy', ['redHerring', 'strategy'])
        );
        $this->assertEquals(
            'strategy',
            $this->scdStrategyChecker->getStrategy('strategy', ['strategy', 'redHerring'])
        );
    }

    public function testGetStrategyFallback()
    {
        $this->loggerMock
            ->expects($this->exactly(3))
            ->method('warning');

        $this->assertEquals(
            'firstStrategy',
            $this->scdStrategyChecker->getStrategy('strategy', ['firstStrategy', 'redHerring'])
        );
        $this->assertEquals(
            'firstStrategy',
            $this->scdStrategyChecker->getStrategy('', ['firstStrategy', 'redHerring'])
        );
        $this->assertEquals(
            '',
            $this->scdStrategyChecker->getStrategy('strategy', ['', 'redHerring'])
        );
    }

    public function testGetStrategyOutOfBounds()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method('warning');

        $this->expectException(\OutOfRangeException::class);
        $this->scdStrategyChecker->getStrategy('strategy', []);
    }

    public function testGetStrategyBadConversion()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method('warning');

        $this->expectExceptionMessage('Array to string conversion');
        $this->scdStrategyChecker->getStrategy('strategy', [[], []]);
    }

    public function testAllowedStrategies()
    {
    }
}
