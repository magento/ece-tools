<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema\Validator;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Config\Schema\Validator\Range;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RangeTest extends TestCase
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $errorInfoMock = $this->createStub(ErrorInfo::class);
        $this->resultFactory = new ResultFactory($errorInfoMock);
    }

    /**
     * Test validate method.
     *
     * @return void
     */
    public function testValidate(): void
    {
        $validator = new Range($this->resultFactory, 0, 32);

        $this->assertEquals(new Success(), $validator->validate('SOME_VARIABLE', 4));
    }

    /**
     * Test validate method with error.
     *
     * @return void
     */
    public function testValidateWithError(): void
    {
        $validator = new Range($this->resultFactory, 0, 9);

        $this->assertEquals(
            new Error(
                'The SOME_VARIABLE variable contains an invalid value 10. Use an integer value from 0 to 9.'
            ),
            $validator->validate('SOME_VARIABLE', 10)
        );
    }
}
