<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RepositoryFactoryTest extends TestCase
{
    public function testCreate()
    {
        $factory = new RepositoryFactory();

        $items = [
            'some_item' => 123,
            'some_item2' => 456,
        ];

        $repository = $factory->create($items);

        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertEquals($repository->get('some_item'), 123);
        $this->assertEquals($repository->get('some_item2'), 456);
    }
}
