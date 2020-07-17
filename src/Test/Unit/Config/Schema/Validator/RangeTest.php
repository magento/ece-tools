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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RangeTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createTestProxy(
            ResultFactory::class,
            [
                $this->createMock(ErrorInfo::class)
            ]
        );
    }

    public function testValidate(): void
    {
        $validator = new Range($this->resultFactoryMock, 0, 32);

        $this->assertEquals(new Success(), $validator->validate('SOME_VARIABLE', 4));
    }

    public function testValidateWithError(): void
    {
        $validator = new Range($this->resultFactoryMock, 0, 9);

        $this->assertEquals(
            new Error(
                'The SOME_VARIABLE variable contains an invalid value 10. Use an integer value from 0 to 9.'
            ),
            $validator->validate('SOME_VARIABLE', 10)
        );
    }
}
