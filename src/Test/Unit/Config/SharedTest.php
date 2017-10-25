<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Shared;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SharedTest extends TestCase
{
    /**
     * @var Shared
     */
    private $shared;

    /**
     * @var Shared\Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->createMock(Shared\Reader::class);

        $this->shared = new Shared(
            $this->readerMock
        );
    }

    public function testGet()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

        $this->assertSame('value1', $this->shared->get('key1'));
        $this->assertSame('value2', $this->shared->get('key2'));
        $this->assertSame(null, $this->shared->get('undefined'));
        $this->assertSame('default_val', $this->shared->get('undefined', 'default_val'));
    }
}
