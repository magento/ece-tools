<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\CloudVariableEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class CloudVariableEncoderTest extends TestCase
{
    /**
     * @var CloudVariableEncoder
     */
    private $encoder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->encoder = new CloudVariableEncoder();
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

    public function testDecode()
    {
        $this->assertSame(
            [
                'someKey' => 'someValue'
            ],
            $this->encoder->decode('eyJzb21lS2V5Ijoic29tZVZhbHVlIn0')
        );
    }
}
