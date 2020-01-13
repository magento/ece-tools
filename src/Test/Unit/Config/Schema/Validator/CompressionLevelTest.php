<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema\Validator;

use Magento\MagentoCloud\Config\Schema\Validator\CompressionLevel;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class CompressionLevelTest extends TestCase
{
    /**
     * @var CompressionLevel
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

        $this->validator = new CompressionLevel(
            $this->resultFactoryMock
        );
    }

    public function testValidate(): void
    {
        $this->assertEquals(new Success(), $this->validator->validate('SOME_VARIABLE', 4));
    }

    public function testValidateWithError(): void
    {
        $this->assertEquals(
            new Error(
                'The SOME_VARIABLE variable contains an invalid value 10. Use an integer value from 0 to 9.'
            ),
            $this->validator->validate('SOME_VARIABLE', 10)
        );
    }
}
