<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
                [DeployInterface::VAR_STATIC_CONTENT_SYMLINK => false],
                [DeployInterface::VAR_STATIC_CONTENT_SYMLINK => Environment::VAL_DISABLED]
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
                [DeployInterface::VAR_SCD_EXCLUDE_THEMES => 'theme'],
                ['STATIC_CONTENT_EXCLUDE_THEMES' => 'theme']
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
                [DeployInterface::VAR_SCD_THREADS => 3],
                [DeployInterface::VAR_STATIC_CONTENT_THREADS => '3']
            ],
            [
                [DeployInterface::VAR_SCD_THREADS => 0],
                [DeployInterface::VAR_STATIC_CONTENT_THREADS => '0']
            ],
            [
                [],
                [DeployInterface::VAR_STATIC_CONTENT_THREADS => 'test']
            ],
            [
                [DeployInterface::VAR_SKIP_SCD => true],
                [DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT => Environment::VAL_DISABLED]
            ],
            [
                [DeployInterface::VAR_SKIP_SCD => false],
                [DeployInterface::VAR_DO_DEPLOY_STATIC_CONTENT => 0]
            ],
            [
                [],
                [DeployInterface::VAR_VERBOSE_COMMANDS => 'wrong_value']
            ]
        ];
    }

    /**
     * @param string $staticContentThreads
     * @param array $expectedResult
     * @dataProvider getEnvScdDataProvider
     */
    public function testGetEnvScd(string $staticContentThreads, array $expectedResult)
    {
        $this->environmentMock->expects($this->any())
            ->method('getVariables')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with(DeployInterface::VAR_STATIC_CONTENT_THREADS)
            ->willReturn($staticContentThreads);

        $this->assertSame($expectedResult, $this->environmentConfig->getAll());
    }

    /**
     * @return array
     */
    public function getEnvScdDataProvider(): array
    {
        return [
            [
                '5',
                ['SCD_THREADS' => 5]
            ],
            [
                '0',
                ['SCD_THREADS' => 0]
            ],
            [
                'test',
                []
            ],
            [
                '',
                []
            ],
            [
                false,
                []
            ]
        ];
    }
}
