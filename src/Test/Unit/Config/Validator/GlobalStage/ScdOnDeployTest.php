<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ScdOnDeployTest extends TestCase
{
    /**
     * @var ScdOnDeploy
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $deployConfigMock;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $scdOnBuildMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->deployConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->scdOnBuildMock = $this->createMock(ScdOnBuild::class);

        $this->validator = new ScdOnDeploy(
            $this->resultFactoryMock,
            $this->globalConfigMock,
            $this->environmentMock,
            $this->deployConfigMock,
            $this->scdOnBuildMock
        );
    }

    public function testValidateSuccess()
    {
        $success = new Result\Success();
        $error = new Result\Error('some error');

        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($success);
        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->deployConfigMock->method('get')
            ->with(DeployInterface::VAR_SKIP_SCD)
            ->willReturn(false);
        $this->scdOnBuildMock->method('validate')
            ->willReturn($error);

        $this->assertSame($success, $this->validator->validate());
    }

    public function testValidateError()
    {
        $success = new Result\Success();

        $this->resultFactoryMock->expects($this->never())
            ->method('success');
        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->environmentMock->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(Environment::VAL_ENABLED);
        $this->deployConfigMock->method('get')
            ->with(DeployInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $this->scdOnBuildMock->method('validate')
            ->willReturn($success);

        $this->assertInstanceOf(Result\Error::class, $this->validator->validate());
    }

    public function testGetErrors()
    {
        $success = new Result\Success();

        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->environmentMock->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(Environment::VAL_ENABLED);
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn($success);
        $this->resultFactoryMock->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['SCD_ON_DEMAND variable is enabled'],
                ['SKIP_SCD variable is enabled'],
                ['SCD on build is enabled']
            );

        $results = $this->validator->getErrors();

        $this->assertCount(3, $results);
        $this->assertContainsOnlyInstancesOf(Result\Error::class, $results);
    }
}
