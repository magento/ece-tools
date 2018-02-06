<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\StrategyResolver;
use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class StrategyResolverTest extends TestCase
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
     * @var StrategyResolver
     */
    private $strategyResolver;

    /**
     * Set up the test object.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->strategyResolver = new StrategyResolver(
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
     * Get strategies when in the trivial case, when it's straightforward.
     *
     * @param array $versionMap
     * @param string $expectedStrategy
     * @param string $desiredStrategy
     * @dataProvider getStrategyDataProvider
     */
    public function testGetStrategy(
        array $versionMap,
        string $desiredStrategy,
        string $expectedStrategy
    ) {
        $this->magentoVersionMock->expects($this->atLeastOnce())
            ->method('satisfies')
            ->willReturnMap($versionMap);

        $this->assertEquals(
            $expectedStrategy,
            $this->strategyResolver->getStrategy($desiredStrategy)
        );
    }

    /**
     * @return array
     */
    public function getStrategyDataProvider()
    {
        return [
            [[['2.1.*', true]], 'some', StrategyResolver::DEFAULT_STRATEGY],
            [[['2.1.*', true]], 'standard', StrategyResolver::DEFAULT_STRATEGY],
            [[['2.1.*', true]], 'quick', StrategyResolver::DEFAULT_STRATEGY],
            [[['2.1.*', false], ['>=2.2', true]], 'standard', StrategyResolver::DEFAULT_STRATEGY],
            [[['2.1.*', false], ['>=2.2', true]], 'quick', 'quick'],
            [[['2.1.*', false], ['>=2.2', true]], 'compact', 'compact'],
            [[['2.1.*', false], ['>=2.2', true]], 'some', StrategyResolver::DEFAULT_STRATEGY],
        ];
    }
}
