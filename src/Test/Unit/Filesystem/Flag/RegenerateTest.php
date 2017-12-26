<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\Flag\Regenerate;
use PHPUnit\Framework\TestCase;

class RegenerateTest extends TestCase
{
    /**
     * @var Regenerate
     */
    private $flag;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->flag = new Regenerate();
    }

    public function testGetPath()
    {
        $this->assertEquals(
            'var/.regenerate',
            $this->flag->getPath()
        );
    }
}
