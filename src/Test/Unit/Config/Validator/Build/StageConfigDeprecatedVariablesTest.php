<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\StageConfigDeprecatedVariables;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * @inheritdoc
 */
class StageConfigDeprecatedVariablesTest extends TestCase
{
    /**
     * @var StageConfigDeprecatedVariables
     */
    private $validator;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new StageConfigDeprecatedVariables(
            $this->environmentReaderMock,
            $this->resultFactoryMock,
            new Schema()
        );
    }

    public function testValidateSuccess()
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with('success', [])
            ->willReturn($this->createMock(Success::class));

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }
}
