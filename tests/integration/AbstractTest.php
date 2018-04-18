<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\DB\ConnectionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Adds sandbox project installation and cleanup abstract features.
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @var Bootstrap
     * @deprecated
     */
    protected $bootstrap;

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run();
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        Bootstrap::getInstance()->destroy();
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::getInstance();

        Bootstrap::getInstance()->execute(sprintf(
            'cd %s && composer install -n --no-dev --no-progress',
            Bootstrap::getInstance()->getSandboxDir()
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        Bootstrap::getInstance()->execute(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            Bootstrap::getInstance()->getSandboxDir()
        ));
        Bootstrap::getInstance()->createApplication([])->getContainer()
            ->get(ConnectionInterface::class)
            ->close();
        Bootstrap::getInstance()->execute(sprintf(
            'cd %s && rm -rf vendor/*',
            Bootstrap::getInstance()->getSandboxDir()
        ));
    }
}
