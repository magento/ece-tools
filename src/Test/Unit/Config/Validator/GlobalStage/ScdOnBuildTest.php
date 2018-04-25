<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Stage\Build as BuildConfig;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var GlobalSection|Mock
     */
    private $globalConfigMock;

    /**
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @var ConfigFileStructure|Mock
     */
    private $configFileStructureMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);
        $this->configFileStructureMock = $this->createMock(ConfigFileStructure::class);

        $this->scdOnBuild = new ScdOnBuild(
            $this->resultFactoryMock,
            $this->globalConfigMock,
            $this->buildConfigMock,
            $this->configFileStructureMock
        );
    }

    public function testExecute()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_SCD)
            ->willReturn(false);
        $resultMock = $this->createMock(Result\Success::class);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);

        $this->scdOnBuild->validate();
    }

    public function testExecuteWithNotValidConfig()
    {
        $resultMock = $this->createMock(Result\Error::class);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_SKIP_SCD)
            ->willReturn(false);

        $this->scdOnBuild->validate();
    }
}
