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
        $this->assertEquals($expected, $this->lineFormatter->format($record));
    }

    /**
     * @return array
     */
    public function formatDataProvider(): array
    {
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
