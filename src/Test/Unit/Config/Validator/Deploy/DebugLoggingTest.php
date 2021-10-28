<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\DebugLogging;
use Magento\MagentoCloud\Config\Magento\System;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * {@inheritdoc}
 */
class DebugLoggingTest extends TestCase
{
    /**
     * @var DebugLogging
     */
    private $validator;

    /**
     * @var System|MockObject
     */
    private $configValidatorMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configValidatorMock = $this->createMock(System::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new DebugLogging(
            $this->configValidatorMock,
            $this->environmentMock,
            $this->resultFactoryMock
        );
    }

    public function testValidateNotMasterBranch()
    {
        $success = new Success();

        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->configValidatorMock->expects($this->never())
            ->method('get');
        $this->resultFactoryMock->expects($this->never())
            ->method('error');
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($success);

        $this->assertSame($success, $this->validator->validate());
    }

    public function testValidateValid()
    {
        $success = new Success();

        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->configValidatorMock->expects($this->once())
            ->method('get')
            ->with(DebugLogging::CONFIG_PATH)
            ->willReturn('0');
        $this->resultFactoryMock->expects($this->never())
            ->method('error');
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($success);

        $this->assertSame($success, $this->validator->validate());
    }

    public function testValidateNotValid()
    {
        $error = new Error('was not valid');

        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->configValidatorMock->expects($this->once())
            ->method('get')
            ->with(DebugLogging::CONFIG_PATH)
            ->willReturn('1');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('Debug logging is enabled in Magento')
            ->willReturn($error);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');

        $this->assertSame($error, $this->validator->validate());
    }
}
