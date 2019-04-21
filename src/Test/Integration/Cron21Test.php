<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php70
 */
class Cron21Test extends CronTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.1.17');
    }

    /**
     * @return array
     */
    public function cronDataProvider(): array
    {
        return [
            # Test
            ['version' => '2.1.17', 'locale' => 'fr_FR'],
            ['version' => '2.1.16', 'locale' => 'fr_FR'],
            ['version' => '2.1.15', 'locale' => 'fr_FR'],
            ['version' => '2.1.14', 'locale' => 'fr_FR'],
            ['version' => '2.1.13', 'locale' => 'fr_FR'],
            ['version' => '2.1.12', 'locale' => 'fr_FR'],
            ['version' => '2.1.11', 'locale' => 'fr_FR'],
            ['version' => '2.1.10', 'locale' => 'fr_FR'],
            ['version' => '2.1.9', 'locale' => 'fr_FR'],


/*

            ['version' => '2.1.4', 'locale' => 'fr_FR'],
            ['version' => '2.1.6', 'locale' => 'ar_KW'],
            ['version' => '2.1.11', 'locale' => 'fr_FR'],
            ['version' => '2.1.14', 'locale' => 'ar_KW'],
            ['version' => '2.1.*', 'locale' => 'fr_FR'],

*/
        ];
    }
}
