<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ScdStrategy;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ScdStrategyTest extends TestCase
{
    /**
     * @var GlobalConfig|Mock
     */
    private $globalConfigMock;

    /**
     * @var StageConfigInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var ScdStrategy
     */
    private $scdStrategyValidator;

    protected function setUp()
    {
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(StageConfigInterface::class);

        $this->scdStrategyValidator = new ScdStrategy(
            new ResultFactory(),
            $this->globalConfigMock,
            $this->stageConfigMock
        );
    }

    public function testValidateSuccessScdOnDemand()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->stageConfigMock->expects($this->never())
            ->method('get')
            ->with(StageConfigInterface::VAR_SCD_STRATEGY);

        $result = $this->scdStrategyValidator->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    /**
     * @param string $strategyName
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidateScdOnDemandDisabled(string $strategyName, string $expectedResultClass)
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SCD_STRATEGY)
            ->willReturn($strategyName);

        $result = $this->scdStrategyValidator->validate();

        $this->assertInstanceOf($expectedResultClass, $result);
    }

    public function validateDataProvider()
    {
        return [
            ['test', Error::class],
            ['quik', Error::class],
            ['quick', Success::class],
            ['compac', Error::class],
            ['compakt', Error::class],
            ['compact', Success::class],
            ['stanart', Error::class],
            ['standard', Success::class],
        ];
    }
}
