<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\ValidatorFactory;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\GlobalStage\SkipHtmlMinification;
use Magento\MagentoCloud\Config\Validator\IdealState;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class IdealStateTest extends TestCase
{
    /**
     * @var IdealState
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ValidatorFactory|MockObject
     */
    private $validatorFactoryMock;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $scdOnBuildMock;

    /**
     * @var PostDeploy|MockObject
     */
    private $postDeployMock;

    /**
     * @var SkipHtmlMinification|MockObject
     */
    private $skipHtmlMinificationMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);

        $this->scdOnBuildMock = $this->createMock(ScdOnBuild::class);
        $this->postDeployMock = $this->createMock(PostDeploy::class);
        $this->skipHtmlMinificationMock = $this->createMock(SkipHtmlMinification::class);

        $this->validatorFactoryMock->method('create')
            ->willReturnMap([
                [ScdOnBuild::class, $this->scdOnBuildMock],
                [PostDeploy::class, $this->postDeployMock],
                [SkipHtmlMinification::class, $this->skipHtmlMinificationMock],
            ]);

        $this->validator = new IdealState($this->resultFactoryMock, $this->validatorFactoryMock);
    }

    public function testValidateSuccess()
    {
        $result = new Success();

        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $this->postDeployMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $this->skipHtmlMinificationMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());

        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($result);
        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->assertSame($result, $this->validator->validate());
    }

    public function testValidateError()
    {
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('SCD validation failed'));
        $this->postDeployMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('Post Deploy validation failed', 'Suggestion for post_deploy'));
        $this->skipHtmlMinificationMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('Skip HTML minification error', 'Set SKIP_HTML_MINIFICATION'));

        $this->resultFactoryMock->expects($this->atLeastOnce())
            ->method('error')
            ->willReturnCallback(function ($message, $suggestion = '') {
                return new Error($message, $suggestion);
            });
        $this->resultFactoryMock->expects($this->never())
            ->method('success');

        $result = $this->validator->validate();

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame('The configured state is not ideal', $result->getError());

        $suggestion = 'SCD validation failed' . PHP_EOL . PHP_EOL;
        $suggestion .= 'Post Deploy validation failed' . PHP_EOL;
        $suggestion .= '  Suggestion for post_deploy' . PHP_EOL . PHP_EOL;
        $suggestion .= 'Skip HTML minification error' . PHP_EOL;
        $suggestion .= '  Set SKIP_HTML_MINIFICATION';
        $this->assertSame($suggestion, $result->getSuggestion());
    }

    public function testGetErrorsSuccess()
    {
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $this->postDeployMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $this->skipHtmlMinificationMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());

        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testGetErrorsError()
    {
        $scdBuildError = new Error('The SCD is not set for the build stage');
        $postDeployError = new Error('Post-deploy hook is not configured');
        $skipMinificationError = new Error('Skip HTML minification is disabled');

        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn($scdBuildError);
        $this->postDeployMock->expects($this->once())
            ->method('validate')
            ->willReturn($postDeployError);
        $this->skipHtmlMinificationMock->expects($this->once())
            ->method('validate')
            ->willReturn($skipMinificationError);

        $this->resultFactoryMock->expects($this->never())
            ->method('success');

        $result = $this->validator->getErrors();
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Error::class, $result);
        $this->assertContains($scdBuildError, $result);
        $this->assertContains($postDeployError, $result);
        $this->assertContains($skipMinificationError, $result);
    }
}
