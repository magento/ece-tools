<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
    protected function setUp(): void
    {
        $this->systemList = new SystemList(
            'tools_root',
            'magento_root'
        );
    }

    public function testGetRoot(): void
    {
        $this->assertSame(
            'tools_root',
            $this->systemList->getRoot()
        );
    }

    public function testGetMagentoRoot(): void
    {
        $this->assertSame(
            'magento_root',
            $this->systemList->getMagentoRoot()
        );
    }

    public function testGetConfig(): void
    {
        $this->assertSame(
            'tools_root/config',
            $this->systemList->getConfig()
        );
    }
}
