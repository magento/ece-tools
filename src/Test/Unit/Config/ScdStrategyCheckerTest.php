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
     * Set up the test object.
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

    /**
     * Ensure the fallback offset set properly.
     */
    public function testFallbackOffset()
    {
        $this->assertEquals(
            0,
            $this->scdStrategyChecker::FALLBACK_OFFSET
        );
    }

    /**
     * Get strategies when in the trivial case, when it's straightforward.
     */
    public function testGetStrategyTrivial()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

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

    /**
     * Get strategies in the fallback case when the desired strategy is not available.
     */
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

    /**
     * Throw an exception when the list of allowed strategies is empty.
     */
    public function testGetStrategyOutOfBounds()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

        $this->expectException(\OutOfRangeException::class);
        $this->scdStrategyChecker->getStrategy('strategy', []);
    }

    /**
     * Throw an exception when the strategies are given as something other than strings.
     */
    public function testGetStrategyBadConversion()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

        $this->expectExceptionMessage('Array to string conversion');
        $this->scdStrategyChecker->getStrategy('strategy', [[], []]);
    }

    /**
     * Get allowed strategies when the list is empty.
     */
    public function testAllowedStrategiesFallback()
    {
        $this->magentoVersionMock
            ->expects($this->atLeast(1))
            ->method('satisfies')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('2.'),
                    $this->stringContains('*')
                )
            );

        $this->assertEquals(
            ['standard'],
            $this->scdStrategyChecker->getAllowedStrategies()
        );
    }

    /**
     * Get allowed strategies when Magento is on 2.1.
     */
    public function testAllowedStrategiesFirst()
    {
        $versionMap = [
            ['2.1.*', true],
            ['2.2.*', false],
        ];

        $this->magentoVersionMock
            ->expects($this->atLeast(1))
            ->method('satisfies')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('2.'),
                    $this->stringContains('*')
                )
            )
            ->willReturnMap($versionMap);

        $this->assertEquals(
            ['standard'],
            $this->scdStrategyChecker->getAllowedStrategies()
        );
    }

    /**
     * Get allowed strategies when Magento is on 2.2.
     */
    public function testAllowedStrategiesSecond()
    {
        $versionMap = [
            ['2.1.*', false],
            ['2.2.*', true],
        ];

        $this->magentoVersionMock
            ->expects($this->atLeast(1))
            ->method('satisfies')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('2.'),
                    $this->stringContains('*')
                )
            )
            ->willReturnMap($versionMap);

        $this->assertEquals(
            ['standard', 'quick', 'compact'],
            $this->scdStrategyChecker->getAllowedStrategies()
        );
    }
}
