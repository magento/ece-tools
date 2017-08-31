<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\ComponentInfo;
use Magento\MagentoCloud\Util\ComponentVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ComponentInfoTest extends TestCase
{
    /**
     * @var ComponentVersion|Mock
     */
    private $componentVersionMock;

    /**
     * @var ComponentInfo
     */
    private $componentInfo;


    protected function setUp()
    {
        $this->componentVersionMock = $this->createMock(ComponentVersion::class);

        $this->componentInfo = new ComponentInfo(
            $this->componentVersionMock
        );
    }


    public function testGet()
    {

    }
}
