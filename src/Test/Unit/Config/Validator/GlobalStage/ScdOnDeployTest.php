<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

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
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->deployConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->scdOnBuildMock = $this->createMock(ScdOnBuild::class);

        $this->validator = new ScdOnDeploy(
            $this->resultFactoryMock,
            $this->globalConfigMock,
            $this->deployConfigMock,
            $this->scdOnBuildMock
        );
    }

    public function testValidate()
    {
        $this->resultFactoryMock->expects($this->once())
            ->method('success');
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SKIP_SCD)
            ->willReturn(false);
        $resultMock = $this->createMock(Result\Error::class);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);

        $this->validator->validate();
    }

    public function testGetErrors()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $resultMock = $this->createMock(Result\Success::class);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);
        $this->resultFactoryMock->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['SCD_ON_DEMAND variable is enabled'],
                ['SKIP_SCD variable is enabled'],
                ['SCD on build is enabled']
            );

        $this->assertCount(3, $this->validator->getErrors());
    }
}
