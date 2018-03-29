<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
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

    public function testCreateSuccessResult()
    {
        $this->assertInstanceOf(
            Result\Success::class,
            $this->resultFactory->create(ResultInterface::SUCCESS)
        );
    }

    public function testCreateErrorResult()
    {
        /** @var Result\Error $result */
        $result = $this->resultFactory->create(ResultInterface::ERROR, [
            'error' => 'some error',
        ]);

        $this->assertInstanceOf(
            Result\Error::class,
            $result
        );
        $this->assertEquals('some error', $result->getError());
    }
}
