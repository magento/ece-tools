<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger\Processor;

use Magento\MagentoCloud\App\Logger\Processor\SanitizeProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SanitizeProcessorTest extends TestCase
{
    /**
     * @param array $record
     * @param array $expected
     * @dataProvider invokeDataProvider
     */
    public function testInvoke(array $record, array $expected)
    {
        $this->assertEquals($expected, (new SanitizeProcessor)($record));
    }

    /**
     * @return array
     */
    public function invokeDataProvider()
    {
        return [
            [
                ['message' => 'some message'],
                ['message' => 'some message']
            ],
            [
                ['message' => 'some message with admin password --admin-password=\'Ks81bUSl13Osd\''],
                ['message' => 'some message with admin password --admin-password=\'******\''],
            ],
            [
                ['message' => 'some message with db password --db-password=\'Ks81bUSl13Osd\''],
                ['message' => 'some message with db password --db-password=\'******\''],
            ],
            [
                ['message' => 'some message with db password --db-password=\'--db-password\''],
                ['message' => 'some message with db password --db-password=\'******\''],
            ],
            [
                ['message' => 'some text --admin-password=\'Ks81bUSl13Osd\' some text'],
                ['message' => 'some text --admin-password=\'******\' some text'],
            ],
            [
                ['message' => 'some text --admin-password=\'Ks81bUSl13Osd\' --db-password=\'Ks81bUSl13Osd\' some text'],
                ['message' => 'some text --admin-password=\'******\' --db-password=\'******\' some text'],
            ],
            [
                ['message' => 'some text --admin-password=\'Ks81\'bUSl\'13Osd\''],
                ['message' => 'some text --admin-password=\'******\''],
            ],
            [
                ['message' => 'some text --admin-password=\'' . escapeshellarg("Ks81b'USl'13Osd") . '\''],
                ['message' => 'some text --admin-password=\'******\''],
            ],
        ];
    }
}
