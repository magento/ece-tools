<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SystemListTest extends TestCase
{
    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemList = new SystemList(
            'tools_root',
            'magento_root'
        );
    }

    public function testGetRoot()
    {
        $this->assertSame(
            'tools_root',
            $this->systemList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $this->assertSame(
            'magento_root',
            $this->systemList->getMagentoRoot()
        );
    }
}
