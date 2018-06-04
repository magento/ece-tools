<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ContainerInterface|Mock
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->resultFactory = new ResultFactory(
            $this->containerMock
        );
    }

    public function testCreateSuccessResult()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Result\Success::class)
            ->willReturn(new Result\Success());

        $this->resultFactory->create(ResultInterface::SUCCESS);
    }

    public function testCreateErrorResult()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Result\Error::class, [
                'message' => 'some error',
                'suggestion' => 'some suggestion',
            ])->willReturn(new Result\Error('some error', 'some suggestion'));

        /** @var Result\Error $result */
        $result = $this->resultFactory->create(ResultInterface::ERROR, [
            'error' => 'some error',
            'suggestion' => 'some suggestion',
        ]);

        $this->assertInstanceOf(
            Result\Error::class,
            $result
        );
        $this->assertEquals('some error', $result->getError());
        $this->assertEquals('some suggestion', $result->getSuggestion());
    }
}
