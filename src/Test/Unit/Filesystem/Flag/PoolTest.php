<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

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

    protected function setUp()
    {
        $this->pool = new Pool([
            'key1' => 'path1',
            'key2' => 'path2'
        ]);
    }

    public function testGetFlagOne()
    {
        $this->assertEquals('path1', $this->pool->get('key1'));
    }

    public function testGetFlagTwo()
    {
        $this->assertEquals('path2', $this->pool->get('key2'));
    }

    public function testGetFlagNotExists()
    {
        $flagPath = $this->pool->get('key3');

        $this->assertNull($flagPath);
    }
}
