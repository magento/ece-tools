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
 * @group php71
 */
class Cron22Test extends CronTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.2.0');
    }

    /**
     * @return array
     */
    public function cronDataProvider(): array
    {
        return [
            ['version' => '2.2.0', 'locale' => 'fr_FR'],
            ['version' => '2.2.5', 'locale' => 'ar_KW'],
            ['version' => '2.2.*', 'locale' => 'fr_FR'],
        ];
    }
}
