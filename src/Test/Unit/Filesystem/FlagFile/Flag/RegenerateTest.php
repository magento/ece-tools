<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\FlagFile;

use Magento\MagentoCloud\Filesystem\FlagFile\Flag\Regenerate;
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

    public function testGetKey()
    {
        $this->assertEquals(
            Regenerate::KEY,
            $this->flag->getKey()
        );
    }

    public function testGetPath()
    {
        $this->assertEquals(
            'var/.regenerate',
            $this->flag->getPath()
        );
    }
}
