<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\App\Logger\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SanitizerTest extends TestCase
{
    /**
     * @param string $message
     * @param string $expectedMesssage
     * @dataProvider invokeDataProvider
     */
    public function testInvoke(string $message, string $expectedMesssage)
    {
        $this->assertEquals($expectedMesssage, (new Sanitizer())->sanitize($message));
    }

    /**
     * @return array
     */
    public function invokeDataProvider()
    {
        return [
            [
                'some message',
                'some message',
            ],
            [
                'some message with admin password --admin-password=\'Ks81bUSl13Osd\'',
                'some message with admin password --admin-password=\'******\'',
            ],
            [
                'some message with db password --db-password=\'Ks81bUSl13Osd\'',
                'some message with db password --db-password=\'******\'',
            ],
            [
                'some message with db password --db-password=\'--db-password\'',
                'some message with db password --db-password=\'******\'',
            ],
            [
                'some text --admin-password=\'Ks81bUSl13Osd\' some text',
                'some text --admin-password=\'******\' some text',
            ],
            [
                'some text --admin-password=\'Ks81bUSl13Osd\' --db-password=\'Ks81bUSl13Osd\' some text',
                'some text --admin-password=\'******\' --db-password=\'******\' some text',
            ],
            [
                'some text --admin-password=\'' . escapeshellarg("Ks81b'USl'13Osd") . '\'',
                'some text --admin-password=\'******\'',
            ],
            [
                'Command: bash -c "set -o pipefail; timeout 3600 mysqldump -h \'127.0.0.1\' -P \'3304\''
                    . ' -u \'abcdefghijklm_stg\' -p\'OmgSuperSecretPasswordDoNotLeak\' \'abcdefghijklm_stg\''
                    . ' --single-transaction --no-autocommit --quick | gzip > /tmp/dump-1525977618.sql.gz',
                'Command: bash -c "set -o pipefail; timeout 3600 mysqldump -h \'127.0.0.1\' -P \'3304\''
                    . ' -u \'abcdefghijklm_stg\' -p\'******\' \'abcdefghijklm_stg\''
                    . ' --single-transaction --no-autocommit --quick | gzip > /tmp/dump-1525977618.sql.gz',
            ],
            [
                'Command: bash -c "set -o pipefail; timeout 3600 mysqldump -h \'127.0.0.1\' -P \'3304\''
                    . ' -u \'abcdefghijklm_stg\' \'abcdefghijklm_stg\' --single-transaction --no-autocommit'
                    . ' --quick | gzip > /tmp/dump-1525977618.sql.gz',
                'Command: bash -c "set -o pipefail; timeout 3600 mysqldump -h \'127.0.0.1\' -P \'3304\''
                    . ' -u \'abcdefghijklm_stg\' \'abcdefghijklm_stg\' --single-transaction --no-autocommit'
                    . ' --quick | gzip > /tmp/dump-1525977618.sql.gz',
            ],

        ];
    }
}
