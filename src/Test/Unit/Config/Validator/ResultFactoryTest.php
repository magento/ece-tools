<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ResultFactoryTest extends TestCase
{
    public function testCreateSuccessResult()
    {
        $resultFactory = new ResultFactory();

        $result = $resultFactory->create(ResultInterface::SUCCESS);

        $this->assertInstanceOf(Result\Success::class, $result);
    }

    public function testCreateErrorResult()
    {
        $resultFactory = new ResultFactory();

        $result = $resultFactory->create(ResultInterface::ERROR, [
            'error' => 'some error',
            'suggestion' => 'some suggestion',
        ]);

        $this->assertInstanceOf(Result\Error::class, $result);
        $this->assertEquals($result->getError(), 'some error');
        $this->assertEquals($result->getSuggestion(), 'some suggestion');
    }
}
