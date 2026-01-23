<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Validator\Deploy\ResourceConfiguration as ResourceConfigurationValidator;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ResourceConfigurationTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ResourceConfig|MockObject
     */
    private $resourceConfigMock;

    /**
     * @var ResourceConfigurationValidator
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resourceConfigMock = $this->createMock(ResourceConfig::class);
        $this->validator = new ResourceConfigurationValidator(
            $this->resultFactoryMock,
            $this->resourceConfigMock
        );
    }

    /**
     * Test error code method.
     *
     * @return void
     */
    public function testErrorCode(): void
    {
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['resource1' => ['key' => 'value']]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Variable RESOURCE_CONFIGURATION is not configured properly',
                'Add connection information to the following resources: resource1',
                AppError::DEPLOY_WRONG_CONFIGURATION_RESOURCE
            );

        $this->validator->validate();
    }

    /**
     * Test validate method.
     *
     * @param array $resourcesConfig
     * @param bool $expectSuccess
     * @param bool $expectError
     * @param string $expectedResultClass
     * @return void
     * @dataProvider validateDataProvider
     */
    #[DataProvider('validateDataProvider')]
    public function testValidate(
        array $resourcesConfig,
        bool $expectSuccess,
        bool $expectError,
        string $expectedResultClass
    ): void {
        /** @var Result\Success|MockObject $successMock */
        $successMock = $this->createStub(Result\Success::class);
        /** @var Result\Error|MockObject $errorMock */
        $errorMock = $this->createStub(Result\Error::class);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($resourcesConfig);
        $this->resultFactoryMock->expects($expectSuccess ? $this->once() : $this->never())
            ->method('success')
            ->willReturn($successMock);
        $this->resultFactoryMock->expects($expectError ? $this->once() : $this->never())
            ->method('error')
            ->willReturn($errorMock);

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
                'resourcesConfig'      => [],
                'expectSuccess'        => true,
                'expectError'          => false,
                'expectedResultClass'  => Result\Success::class,
            ],
            [
                'resourcesConfig' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
                'expectSuccess'       => true,
                'expectError'         => false,
                'expectedResultClass' => Result\Success::class,
            ],
            [
                'resourcesConfig' => [
                    'some_setup' => [
                        'connection' => 'value',
                    ],
                ],
                'expectSuccess'       => true,
                'expectError'         => false,
                'expectedResultClass' => Result\Success::class,
            ],
            [
                'resourcesConfig' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'some_setup' => [
                        'connection' => 'value',
                    ],
                ],
                'expectSuccess'       => true,
                'expectError'         => false,
                'expectedResultClass' => Result\Success::class,
            ],
            [
                'resourcesConfig' => [
                    'default_setup' => [],
                ],
                'expectSuccess'       => false,
                'expectError'         => true,
                'expectedResultClass' => Result\Error::class,
            ],
            [
                'resourcesConfig' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'some_setup' => [],
                ],
                'expectSuccess'       => false,
                'expectError'         => true,
                'expectedResultClass' => Result\Error::class,
            ],
        ];
    }
}
