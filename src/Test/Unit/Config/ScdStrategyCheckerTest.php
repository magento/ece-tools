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
    const ALLOWED_STRATEGIES = [
        '2.1.*' => ['standard'],
        '2.2.*' => ['standard', 'quick', 'compact'],
    ];

    const FALLBACK_STRATEGY = ['standard'];
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
            $this->magentoVersionMock,
            static::ALLOWED_STRATEGIES,
            static::FALLBACK_STRATEGY
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
     *
     * @dataProvider getStrategyTrivialProvider
     */
    public function testGetStrategyTrivial($expectedStrategy, $desiredStrategy, $availableStrategies)
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

        $this->assertEquals(
            $expectedStrategy,
            $this->scdStrategyChecker->getStrategy($desiredStrategy, $availableStrategies)
        );
    }

    public function getStrategyTrivialProvider()
    {
        return [
            ['strategy', 'strategy', ['strategy']],
            ['strategy', 'strategy', ['redHerring', 'strategy']],
            ['strategy', 'strategy', ['strategy', 'redHerring']],
        ];
    }

    /**
     * Get strategies in the fallback case when the desired strategy is not available.
     *
     * @dataProvider getStrategyFallbackProvider
     */
    public function testGetStrategyFallback($expectedStrategy, $desiredStrategy, $availableStrategies)
    {
        $this->loggerMock
            ->expects($this->exactly(1))
            ->method('warning');

        $this->assertEquals(
            $expectedStrategy,
            $this->scdStrategyChecker->getStrategy($desiredStrategy, $availableStrategies)
        );
    }

    public function getStrategyFallbackProvider()
    {
        return [
            ['firstStrategy', 'strategy', ['firstStrategy', 'redHerring']],
            ['firstStrategy', '', ['firstStrategy', 'redHerring']],
            ['', 'strategy', ['', 'redHerring']],
        ];
    }

    /**
     * Throw an exception when the list of allowed strategies is empty.
     *
     * @expectedException \OutOfRangeException
     */
    public function testGetStrategyOutOfBounds()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

        $this->scdStrategyChecker->getStrategy('strategy', []);
    }

    /**
     * Throw an exception when the strategies are given as something other than strings.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Array to string conversion
     */
    public function testGetStrategyBadConversion()
    {
        $this->loggerMock
            ->expects($this->exactly(0))
            ->method($this->anything());

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
            ->with($this->stringContains('.'));

        $this->assertEquals(
            ['standard'],
            $this->scdStrategyChecker->getAllowedStrategies()
        );
    }

    /**
     * Get allowed strategies depending on the apparent Magento version.
     *
     * @dataProvider allowedStrategiesProvider
     */
    public function testAllowedStrategies($versionMap, $expectedAllowedStrategies)
    {
        $this->magentoVersionMock
            ->expects($this->atLeast(1))
            ->method('satisfies')
            ->with($this->stringContains('.'))
            ->willReturnMap($versionMap);

        $this->assertEquals(
            $expectedAllowedStrategies,
            $this->scdStrategyChecker->getAllowedStrategies()
        );
    }

    /**
     * @return array Version strings and strategies to test against the allowed strategies method.
     */
    public function allowedStrategiesProvider()
    {
        return [
            [
                [
                    ['2.1.*', true],
                    ['2.2.*', false],
                ],
                ['standard'],
            ],
            [
                [
                    ['2.1.*', false],
                    ['2.2.*', true],
                ],
                ['standard', 'quick', 'compact']
            ],
        ];
    }
}
