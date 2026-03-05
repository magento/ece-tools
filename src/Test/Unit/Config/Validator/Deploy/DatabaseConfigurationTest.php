<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\DatabaseConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DatabaseConfigurationTest extends TestCase
{
    /**
     * @var DatabaseConfiguration
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
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        $this->validator = new DatabaseConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock
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
            ->with(DeployInterface::VAR_DATABASE_CONFIGURATION)
            ->willReturn(['wrong config']);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Variable DATABASE_CONFIGURATION is not configured properly',
                'At least host, dbname, username and password options must be configured for default connection',
                AppError::DEPLOY_WRONG_CONFIGURATION_DB
            );

        $this->validator->validate();
    }

    /**
     * Test validate method.
     *
     * @param array $dbConfiguration
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     * @return void
     */
    #[DataProvider('validateDataProvider')]
    public function testValidate(array $dbConfiguration, string $expectedResultClass): void
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_DATABASE_CONFIGURATION)
            ->willReturn($dbConfiguration);

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
                    'table_prefix' => 'test',
                ],
                Error::class,
            ],
            [
                [
                    'table_prefix' => 'test',
                    '_merge' => true,
                ],
                Success::class,
            ],
            [
                [
                    'connection' => [
                        'default' => [
                            'host' => 'some.host'
                        ],
                    ],
                ],
                Error::class,
            ],
            [
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'dbname' => 'dbname',
                            'username' => 'username',
                        ],
                    ],
                ],
                Error::class,
            ],
            [
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'dbname' => 'dbname',
                            'username' => 'username',
                            'password' => ''
                        ],
                    ],
                ],
                Success::class,
            ],
        ];
    }
}
