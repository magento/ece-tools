<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Stage\Build;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $config;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var BuildReader|Mock
     */
    private $buildReaderMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->buildReaderMock = $this->createMock(BuildReader::class);

        $this->config = new Build(
            $this->environmentReaderMock,
            $this->buildReaderMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param array $buildConfig
     * @param mixed $default
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $envConfig, array $buildConfig, $default, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->buildReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($buildConfig);

        $this->assertSame($expectedValue, $this->config->get($name, $default));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                Build::VAR_SCD_STRATEGY,
                [],
                [],
                null,
                '',
            ],
        ];
    }
}
