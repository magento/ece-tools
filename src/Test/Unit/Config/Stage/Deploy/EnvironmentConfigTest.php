<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Stage\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\Deploy\EnvironmentConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class EnvironmentConfigTest extends TestCase
{
    /**
     * @var EnvironmentConfig
     */
    private $environmentConfig;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->environmentConfig = new EnvironmentConfig($this->environmentMock);
    }

    /**
     * @param array $expectedVariables
     * @param array $envVariables
     * @dataProvider getAllDataProvider
     */
    public function testGetAll(array $expectedVariables, array $envVariables)
    {
        $this->environmentMock->method('getVariables')
            ->willReturn($envVariables);

        $this->assertSame(
            $expectedVariables,
            $this->environmentConfig->getAll()
        );
    }

    /**
     * @return array
     */
    public function getAllDataProvider(): array
    {
        return [
            [
                [],
                []
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => '-vvv'],
                [DeployInterface::VAR_VERBOSE_COMMANDS => Environment::VAL_ENABLED]
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => '-vv'],
                [DeployInterface::VAR_VERBOSE_COMMANDS => '-vv']
            ],
            [
                [DeployInterface::VAR_CLEAN_STATIC_FILES => false],
                [DeployInterface::VAR_CLEAN_STATIC_FILES => Environment::VAL_DISABLED]
            ],
            [
                [DeployInterface::VAR_UPDATE_URLS => false],
                [DeployInterface::VAR_UPDATE_URLS => Environment::VAL_DISABLED]
            ],
            [
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK => false],
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK => Environment::VAL_DISABLED]
            ],
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 3],
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '3']
            ],
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 3],
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 3]
            ],
            [
                [],
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 'test']
            ],
            [
                [],
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '10']
            ],
            [
                [],
                [DeployInterface::VAR_VERBOSE_COMMANDS => 'wrong_value']
            ]
        ];
    }
}
