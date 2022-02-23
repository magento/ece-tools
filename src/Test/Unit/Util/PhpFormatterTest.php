<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Util\PhpFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class PhpFormatterTest extends TestCase
{
    /**
     * @var PhpFormatter
     */
    private $formatter;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->formatter = new PhpFormatter(
            $this->magentoVersionMock
        );
    }

    /**
     * @param string $expected
     * @param array $data
     * @throws UndefinedPackageException
     *
     * @dataProvider formatDataProvider
     */
    public function testFormat(string $expected, array $data)
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.5')
            ->willReturn(true);

        $this->assertSame(
            $expected,
            $this->formatter->format($data)
        );
    }

    public function formatDataProvider(): array
    {
        $expected1 = <<<TEXT
<?php
return [
    'some' => 'data'
];

TEXT;
        $expected2 = <<<TEXT
<?php
return [
    'some' => [
        'data' => 'value'
    ]
];

TEXT;

        return [
            [
                $expected1,
                ['some' => 'data'],
            ],
            [
                $expected2,
                ['some' => ['data' => 'value']],
            ],
        ];
    }

    public function testFormatLegacy()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.5')
            ->willReturn(false);

        $expected = <<<TEXT
<?php
return array (
  'some' => 'data',
);

TEXT;

        $this->assertSame(
            $expected,
            $this->formatter->format(['some' => 'data'])
        );
    }
}
