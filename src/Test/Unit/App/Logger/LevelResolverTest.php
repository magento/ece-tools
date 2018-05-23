<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\Config\Log as LogConfig;
use Magento\MagentoCloud\App\Logger\LevelResolver;
use Magento\MagentoCloud\Config\GlobalSection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class LevelResolverTest extends TestCase
{
    /**
     * @var LevelResolver
     */
    private $levelResolver;

    /**
     * @var GlobalSection|Mock
     */
    private $stageConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stageConfig = $this->createMock(GlobalSection::class);
        $this->levelResolver = new LevelResolver($this->stageConfig);
    }

    /**
     * @param string $level
     * @param int $expectedResult
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $level, int $expectedResult)
    {
        $this->assertSame($expectedResult, $this->levelResolver->resolve($level));
    }

    /**
     * @param string $level
     * @param int $expectedResult
     * @dataProvider resolveDataProvider
     */
    public function testResolveOverride(string $level, int $expectedResult)
    {
        $this->stageConfig
            ->method('get')
            ->with(GlobalSection::VAR_MIN_LOGGING_LEVEL)
            ->willReturn($level);

        $this->assertSame($expectedResult, $this->levelResolver->resolve('some level'));
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            ['level' => 'debug', Logger::DEBUG],
            ['level' => 'info', Logger::INFO],
            ['level' => 'notice', Logger::NOTICE],
            ['level' => 'warning', Logger::WARNING],
            ['level' => 'error', Logger::ERROR],
            ['level' => 'critical', Logger::CRITICAL],
            ['level' => 'alert', Logger::ALERT],
            ['level' => 'emergency', Logger::EMERGENCY],
            ['level' => 'someLevel', Logger::NOTICE],
            ['level' => 'debUg', Logger::DEBUG],
            ['level' => 'INFO', Logger::INFO],
            ['level' => 'noTice', Logger::NOTICE],
            ['level' => 'waRning', Logger::WARNING],
            ['level' => 'errOr', Logger::ERROR],
            ['level' => 'criTical', Logger::CRITICAL],
            ['level' => 'alErt', Logger::ALERT],
            ['level' => 'Emergency', Logger::EMERGENCY],
            ['level' => 'invalid', Logger::NOTICE]
        ];
    }
}
