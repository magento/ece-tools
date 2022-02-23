<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Filesystem\Flag\Pool;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class PoolTest extends TestCase
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->pool = new Pool();
    }

    public function testGetFlagOne(): void
    {
        $this->assertEquals('var/.regenerate', $this->pool->get(Manager::FLAG_REGENERATE));
    }

    public function testGetFlagNotExists(): void
    {
        $flagPath = $this->pool->get('key3');

        $this->assertNull($flagPath);
    }
}
