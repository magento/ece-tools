<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\CloudVariable\Encoder;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class EncoderTest extends TestCase
{
    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->encoder = new Encoder();
    }

    public function testEncode()
    {
        $data = [
            'someKey' => 'someValue'
        ];

        $this->assertSame(
            'eyJzb21lS2V5Ijoic29tZVZhbHVlIn0=',
            $this->encoder->encode($data)
        );
    }
}
