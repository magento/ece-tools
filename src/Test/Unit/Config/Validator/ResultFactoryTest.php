<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
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

    /**
     * @var ErrorInfo|MockObject
     */
    private $errorInfoMock;

    protected function setUp()
    {
        $this->errorInfoMock = $this->createMock(ErrorInfo::class);

        $this->resultFactory = new ResultFactory($this->errorInfoMock);
    }

    public function testCreateSuccessResult(): void
    {
        $result = $this->resultFactory->create(ResultInterface::SUCCESS);

        $this->assertInstanceOf(Result\Success::class, $result);
    }

    public function testCreateErrorResult(): void
    {
        $result = $this->resultFactory->create(ResultInterface::ERROR, [
            'error' => 'some error',
            'suggestion' => 'some suggestion',
            'errorCode' => 10
        ]);

        $this->assertInstanceOf(Result\Error::class, $result);
        $this->assertEquals($result->getError(), 'some error');
        $this->assertEquals($result->getSuggestion(), 'some suggestion');
        $this->assertEquals($result->getErrorCode(), 10);
    }

    public function testCreateErrorByCode()
    {
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(Error::DEPLOY_WRONG_BRAINTREE_VARIABLE)
            ->willReturn([
                'title' => 'some title',
                'suggestion' => 'some suggestion'
            ]);

        $result = $this->resultFactory->errorByCode(Error::DEPLOY_WRONG_BRAINTREE_VARIABLE);

        $this->assertInstanceOf(Result\Error::class, $result);
        $this->assertEquals($result->getError(), 'some title');
        $this->assertEquals($result->getSuggestion(), 'some suggestion');
        $this->assertEquals($result->getErrorCode(), Error::DEPLOY_WRONG_BRAINTREE_VARIABLE);
    }
}
