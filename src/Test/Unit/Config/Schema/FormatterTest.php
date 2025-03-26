<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Schema\Formatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Dumper;

/**
 * @inheritDoc
 */
class FormatterTest extends TestCase
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->formatter = new Formatter(
            new Dumper()
        );
    }

    public function testFormat(): void
    {
        $data = [
            'ENV_VARIABLE' => [
                Schema::SCHEMA_DESCRIPTION => 'Some description',
                Schema::SCHEMA_TYPE => 'string',
                Schema::SCHEMA_STAGES => [
                    'global'
                ],
                Schema::SCHEMA_MAGENTO_VERSION => '>=2.1.24',
                Schema::SCHEMA_EXAMPLES => [
                    'Some example 1',
                    'Some example 2'
                ]
            ],
            'ENV_VARIABLE_NO_DUMP' => [
                Schema::SCHEMA_DESCRIPTION => 'Some description',
                Schema::SCHEMA_TYPE => 'string',
                Schema::SCHEMA_STAGES => [
                    'global'
                ],
                Schema::SCHEMA_SKIP_DUMP => true
            ],
        ];
        $expected = file_get_contents(__DIR__ . '/_files/.magento.env.md');

        $this->assertSame(
            $expected,
            $this->formatter->format($data)
        );
    }
}
