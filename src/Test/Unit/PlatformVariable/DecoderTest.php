<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\PlatformVariable\Decoder;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DecoderTest extends TestCase
{
    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->decoder = new Decoder();
    }

    public function testDecode()
    {
        $this->assertSame(
            [
                'someKey' => 'someValue'
            ],
            $this->decoder->decode('eyJzb21lS2V5Ijoic29tZVZhbHVlIn0')
        );
    }
}
