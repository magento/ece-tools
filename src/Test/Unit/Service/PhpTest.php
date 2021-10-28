<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Service\Php;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PhpTest extends TestCase
{
    /**
     * @var Php
     */
    private $phpService;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->phpService = new Php();
    }

    /**
     * Test service version.
     */
    public function testGetVersion()
    {
        $this->assertTrue($this->phpService->getVersion() !== '0');
    }

    /**
     * Test service configuration.
     */
    public function testGetConfiguration()
    {
        $this->assertTrue(
            is_array($this->phpService->getConfiguration()) &&
            array_key_exists('version', $this->phpService->getConfiguration())
        );
    }
}
