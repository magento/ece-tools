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
        $this->container = Bootstrap::getInstance()->createApplication()->getContainer();
        $this->shell = $this->container->get(ShellInterface::class);
        $this->connection = $this->container->get(ConnectionInterface::class);

        $this->shell->execute('composer install -n --no-dev --no-progress');
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->shell->execute('php ./bin/magento setup:uninstall -n');
        $this->shell->execute('rm -rf vendor/*');
        $this->connection->close();
    }
}
