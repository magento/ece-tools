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
     * @return array
     */
    public function cronDataProvider()
    {
        return [
            ['version' => '2.1.4'],
            ['version' => '2.1.*'],
        ];
    }
}
