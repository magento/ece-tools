<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->factory = new ServiceFactory();
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testCreate()
    {
        $this->factory->create(ServiceFactory::SERVICE_CLI, '7.0');
    }

    /**
     * @expectedExceptionMessage Service "php-cli" does not support version "5.6"
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     *
     * @throws ConfigurationMismatchException
     */
    public function testCreateVersionNotSupported()
    {
        $this->factory->create(ServiceFactory::SERVICE_CLI, '5.6');
    }

    /**
     * @expectedExceptionMessage Service "test" is not supported
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     *
     * @throws ConfigurationMismatchException
     */
    public function testCreateServiceNotSupported()
    {
        $this->factory->create('test', '5.6');
    }
}
