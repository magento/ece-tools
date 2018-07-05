<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        Bootstrap::getInstance()->run();
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        Bootstrap::getInstance()->destroy();
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->bootstrap = Bootstrap::getInstance();
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->container = Bootstrap::getInstance()->createApplication()->getContainer();
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->shell = $this->container->get(ShellInterface::class);
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->connection = $this->container->get(ConnectionInterface::class);
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->shell->execute('composer install -n --no-dev --no-progress');
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->shell->execute('php ./bin/magento setup:uninstall -n');
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->shell->execute('rm -rf vendor/*');
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
        $this->connection->close();
        printf("Travis Debugging: %s:%d\n", __FILE__, __LINE__);
    }
}
