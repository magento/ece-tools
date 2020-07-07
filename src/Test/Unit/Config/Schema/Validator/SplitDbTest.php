<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema\Validator;

use Magento\MagentoCloud\Config\Schema\Validator\SplitDb;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SplitDbTest extends TestCase
{
    /**
     * @var SplitDb
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createTestProxy(ResultFactory::class);

        $this->validator = new SplitDb(
            $this->resultFactoryMock
        );
    }

    public function testValidate(): void
    {
        $this->assertEquals(new Success(), $this->validator->validate('SOME_VARIABLE', ['sales', 'quote']));
    }

    public function testValidateWithError(): void
    {
        $this->assertEquals(
            new Error(
                'The SOME_VARIABLE variable contains the invalid value.'
                .' It should be an array with following values:: [quote, sales].'
            ),
            $this->validator->validate('SOME_VARIABLE', ['invalid_value'])
        );
    }
}
