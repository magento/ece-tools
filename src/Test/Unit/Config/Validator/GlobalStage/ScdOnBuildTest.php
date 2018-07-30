<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\Build as BuildConfig;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ScdOnBuildTest extends TestCase
{
    /**
     * @var ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var BuildConfig|MockObject
     */
    private $buildConfigMock;

    /**
     * @var ConfigFileStructure|MockObject
     */
    private $configFileStructureMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);
        $this->configFileStructureMock = $this->createMock(ConfigFileStructure::class);

        $this->scdOnBuild = new ScdOnBuild(
            $this->resultFactoryMock,
            $this->globalConfigMock,
            $this->environmentMock,
            $this->buildConfigMock,
            $this->configFileStructureMock
        );
    }

    public function testExecuteSuccess()
    {
        $success = new Result\Success();

        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_SCD)
            ->willReturn(false);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($success);
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn($success);

        $this->assertSame($success, $this->scdOnBuild->validate());
    }

    public function testExecuteError()
    {
        $error = new Result\Error('some error');

        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->environmentMock->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(Environment::VAL_ENABLED);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($error);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');

        $this->assertInstanceOf(Result\Error::class, $this->scdOnBuild->validate());
    }

    public function testGetErrors()
    {
        $error = new Result\Error('some error');

        $this->globalConfigMock->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->environmentMock->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(Environment::VAL_ENABLED);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($error);
        $this->resultFactoryMock->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['SCD_ON_DEMAND variable is enabled'],
                ['SKIP_SCD variable is enabled']
            );

        $result = $this->scdOnBuild->getErrors();
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Result\Error::class, $result);
    }
}
