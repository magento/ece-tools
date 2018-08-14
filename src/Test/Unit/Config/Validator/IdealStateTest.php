<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\ValidatorFactory;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
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
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->validator = new IdealState(
            $this->resultFactoryMock,
            $this->validatorFactoryMock,
            $this->globalConfigMock
        );
    }

    public function testValidateSuccess()
    {
        $scdOnBuildValidator = $this->createMock(ScdOnBuild::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);
        $result = new Success();

        $scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnBuild::class, $scdOnBuildValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($result);
        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->assertSame($result, $this->validator->validate());
    }

    public function testValidateError()
    {
        $scdOnBuildValidator = $this->createMock(ScdOnBuild::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);

        $scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('SCD validation failed'));
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('Post Deploy validation failed', 'Suggestion for post_deploy'));

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnBuild::class, $scdOnBuildValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(false);
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
        $suggestion .= 'Skip HTML minification is disabled' . PHP_EOL;
        $suggestion .= '  Make sure "SKIP_HTML_MINIFICATION" is set to true in .magento.env.yaml.';
        $this->assertSame($suggestion, $result->getSuggestion());
    }

    public function testGetErrorsSuccess()
    {
        $scdOnBuildValidator = $this->createMock(ScdOnBuild::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);

        $scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnBuild::class, $scdOnBuildValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->assertSame([], $this->validator->getErrors());
    }

    public function testGetErrorsError()
    {
        $scdOnBuildValidator = $this->createMock(ScdOnBuild::class);
        $postDeployValidator = $this->createMock(PostDeploy::class);

        $scdBuildError = new Error('The SCD is not set for the build stage');
        $postDeployError = new Error('Post-deploy hook is not configured');
        $skipMinificationError = new Error('Skip HTML minification is disabled');

        $scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($scdBuildError);
        $postDeployValidator->expects($this->once())
            ->method('validate')
            ->willReturn($postDeployError);

        $this->validatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ScdOnBuild::class, $scdOnBuildValidator],
                [PostDeploy::class, $postDeployValidator],
            ]);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
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