<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RepositoryFactoryTest extends TestCase
{
    /**
     * @var RepositoryFactory
     */
    private $factory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->factory = new RepositoryFactory();
    }

    public function testCreate()
    {
        $items = [
            'some_item' => 1,
        ];

        $repository = $this->factory->create($items);

        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertAttributeSame($items, 'items', $repository);
    }
}
