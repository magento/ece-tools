<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\App\Logger\LevelResolver;
use Magento\MagentoCloud\Config\Environment;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->levelResolver = new LevelResolver(
            $this->environmentMock
        );
    }

    /**
     * @param string $level
     * @param int $expectedResult
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $level, int $expectedResult)
    {
        $this->environmentMock->expects($this->any())
            ->method('getLogLevel')
            ->willReturn(0);

        $this->assertSame($expectedResult, $this->levelResolver->resolve($level));
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
        ];
    }
}
