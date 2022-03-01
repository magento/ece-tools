<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Validator\GlobalStage\SkipHtmlMinification;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * {@inheritdoc}
 */
class SkipHtmlMinificationTest extends TestCase
{
    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SkipHtmlMinification
     */
    private $validator;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new SkipHtmlMinification($this->globalConfigMock, $this->resultFactoryMock);
    }

    public function testValidateSuccess()
    {
        $success = new Result\Success();

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(true);

        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($success);
        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->assertSame($success, $this->validator->validate());
    }

    public function testValidateError()
    {
        $error = new Result\Error('Skip HTML minification error');

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(false);

        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Skip HTML minification is disabled',
                'Make sure "SKIP_HTML_MINIFICATION" is set to true in .magento.env.yaml.'
            )
            ->willReturn($error);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');

        $this->assertSame($error, $this->validator->validate());
    }
}
