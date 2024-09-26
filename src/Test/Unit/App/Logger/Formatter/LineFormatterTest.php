<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Formatter;

use Magento\MagentoCloud\App\Logger\Formatter\LineFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class LineFormatterTest extends TestCase
{
    /**
     * @var LineFormatter
     */
    private $lineFormatter;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->lineFormatter = new LineFormatter(LineFormatter::FORMAT_BASE, null, true, true);
    }

    /**
     * @dataProvider formatDataProvider
     * @param string $expected
     * @param array $record
     */
    public function testFormat(string $expected, array $record)
    {
        if (\Monolog\Logger::API == 3) {
            $record = new \Monolog\LogRecord(
                datetime: \DateTimeImmutable::createFromFormat('j-M-Y H:i:s', '15-Feb-2009 00:00:00'),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: $record['message'],
                context: $record['context']
            );
        }

        $this->assertEquals($expected, $this->lineFormatter->format($record));
    }

    /**
     * @return array
     */
    public function formatDataProvider(): array
    {
        if (\Monolog\Logger::API == 3) {
            return [
                [
                    '[2009-02-15T00:00:00+00:00] WARNING: test' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level' => 'WARNING',
                        'extra' => [],
                        'context' => [],
                    ]
                ],
                [
                    '[2009-02-15T00:00:00+00:00] WARNING: [111] test' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level' => 'WARNING',
                        'extra' => [],
                        'context' => ['errorCode' => 111],
                    ]
                ],
                [
                    '[2009-02-15T00:00:00+00:00] WARNING: [111] test' . PHP_EOL . 'some suggestion' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level' => 'WARNING',
                        'extra' => [],
                        'context' => ['errorCode' => 111, 'suggestion' => 'some suggestion'],
                    ]
                ],
            ];
        } else {
            return [
                [
                    '[%datetime%] WARNING: test' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level_name' => 'WARNING',
                        'extra' => [],
                        'context' => [],
                    ]
                ],
                [
                    '[%datetime%] WARNING: [111] test' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level_name' => 'WARNING',
                        'extra' => [],
                        'context' => ['errorCode' => 111],
                    ]
                ],
                [
                    '[%datetime%] WARNING: [111] test' . PHP_EOL . 'some suggestion' . PHP_EOL,
                    [
                        'message' => 'test',
                        'level_name' => 'WARNING',
                        'extra' => [],
                        'context' => ['errorCode' => 111, 'suggestion' => 'some suggestion'],
                    ]
                ],
            ];
        }
    }
}
