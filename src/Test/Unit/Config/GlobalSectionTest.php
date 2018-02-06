<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\GlobalSection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * @inheritdoc
 */
class GlobalSectionTest extends TestCase
{
    /**
     * @var GlobalSection
     */
    private $config;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);

        $this->config = new GlobalSection($this->environmentReaderMock);
    }

    /**
     * @param string $name
     * @param array $config
     * @param bool $expectedValue
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $config, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([GlobalSection::SECTION_STAGE => $config]);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => true,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => false
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => false
                    ],
                ],
                'expectedValue' => true,
            ],
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => false,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => true
                    ],
                ],
                'expectedValue' => false,
            ],
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION,
                'config' => [
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND_IN_PRODUCTION => true
                    ],
                ],
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config NOT_EXISTS_VALUE was not defined.
     */
    public function testNotExists()
    {
        $this->environmentReaderMock->expects($this->never())
            ->method('read');

        $this->config->get('NOT_EXISTS_VALUE');
    }
}
