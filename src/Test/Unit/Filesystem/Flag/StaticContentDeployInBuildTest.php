<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployInBuild;
use PHPUnit\Framework\TestCase;

class StaticContentDeployInBuildTest extends TestCase
{
    /**
     * @var StaticContentDeployInBuild
     */
    private $flag;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->flag = new StaticContentDeployInBuild();
    }

    public function testGetKey()
    {
        $this->assertEquals(
            StaticContentDeployInBuild::KEY,
            $this->flag->getKey()
        );
    }

    public function testGetPath()
    {
        $this->assertEquals(
            '.static_content_deploy',
            $this->flag->getPath()
        );
    }
}
