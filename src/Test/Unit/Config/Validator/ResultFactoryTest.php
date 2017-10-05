<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;

class ResultFactoryTest extends TestCase
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    public function setUp()
    {
        $this->resultFactory = new ResultFactory();
    }

    public function testCreate()
    {
        $this->assertInstanceOf(
            Result::class,
            $this->resultFactory->create()
        );
    }
}
