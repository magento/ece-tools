<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployPending;
use PHPUnit\Framework\TestCase;

class StaticContentDeployPendingTest extends TestCase
{
    /**
     * @var StaticContentDeployPending
     */
    private $flag;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->flag = new StaticContentDeployPending();
    }

    public function testGetPath()
    {
        $this->assertEquals(
            'var/.static_content_deploy_pending',
            $this->flag->getPath()
        );
    }
}
