<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\GlobalStage;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Stage\Build as BuildConfig;

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
    protected function setUp(): void
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

    public function testExecute(): void
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

    public function testExecuteWithNotValidConfig(): void
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
