<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\SessionConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class SessionConfigurationTest extends TestCase
{
    /**
     * @var SessionConfiguration
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(
            ResultFactory::class,
            [
                'success' => $this->createMock(Success::class),
                'error' => $this->createMock(Error::class)
            ]
        );
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        $this->validator = new SessionConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * Test error code method.
     *
     * @return void
     */
    public function testErrorCode(): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['key' => 'value']);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The SESSION_CONFIGURATION variable is not configured properly',
                'At least "save" option must be configured for session configuration.',
                AppError::DEPLOY_WRONG_CONFIGURATION_SESSION
            );

        $this->validator->validate();
    }

    /**
     * Test validate method.
     *
     * @param        array  $sessionConfiguration
     * @param        string $expectedResultClass
     * @dataProvider validateDataProvider
     * @return void
     */
    #[DataProvider('validateDataProvider')]
    public function testValidate(array $sessionConfiguration, string $expectedResultClass): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($sessionConfiguration);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * Test validate method with valkey configuration.
     *
     * @param        array  $sessionConfiguration
     * @param        string $expectedResultClass
     * @dataProvider validateDataProviderValkey
     * @return void
     */
    #[DataProvider('validateDataProviderValkey')]
    public function testValidateValkey(array $sessionConfiguration, string $expectedResultClass): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($sessionConfiguration);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * Data provider for validate method.
     *
     * @return array
     */
    public static function validateDataProvider(): array
    {
        return [
            [
                [],
                Success::class,
            ],
            [
                [
                    'redis' => ['max_connection' => 10],
                ],
                Error::class,
            ],
            [
                [
                    'redis' => ['max_connection' => 10],
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [
                    'redis' => ['max_connection' => 10],
                    '_merge' => false,
                ],
                Error::class,
            ],
            [
                [
                    'save' => 'redis',
                    'redis' => ['max_connection' => 10],
                    '_merge' => false,
                ],
                Success::class,
            ],
            [
                [
                    'save' => 'redis',
                    'redis' => ['max_connection' => 10],
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [
                    'save' => 'redis'
                ],
                Success::class,
            ],
        ];
    }

    /**
     * Data provider for validate method.
     *
     * @return array
     */
    public static function validateDataProviderValkey(): array
    {
        return [
            [
                [],
                Success::class,
            ],
            [
                [
                    'valkey' => ['max_connection' => 10],
                ],
                Error::class,
            ],
            [
                [
                    'valkey' => ['max_connection' => 10],
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [
                    'valkey' => ['max_connection' => 10],
                    '_merge' => false,
                ],
                Error::class,
            ],
            [
                [
                    'save' => 'valkey',
                    'valkey' => ['max_connection' => 10],
                    '_merge' => false,
                ],
                Success::class,
            ],
            [
                [
                    'save' => 'valkey',
                    'valkey' => ['max_connection' => 10],
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [
                    'save' => 'valkey'
                ],
                Success::class,
            ],
        ];
    }
}
