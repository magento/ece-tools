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
        $this->validator = new ResourceConfigurationValidator($this->resultFactoryMock, $this->resourceConfigMock);
    }

    public function testErrorCode()
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
     * @param array $resourcesConfig
     * @param $successExpects
     * @param $errorExpects
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        array $resourcesConfig,
        $successExpects,
        $errorExpects,
        string $expectedResultClass
    ) {
        /** @var Result\Success|MockObject $successMock */
        $successMock = $this->createMock(Result\Success::class);
        /** @var Result\Error|MockObject $errorMock */
        $errorMock = $this->createMock(Result\Error::class);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($resourcesConfig);
        $this->resultFactoryMock->expects($successExpects)
            ->method('success')
            ->willReturn($successMock);
        $this->resultFactoryMock->expects($errorExpects)
            ->method('error')
            ->willReturn($errorMock);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                'resourcesConfig'=> [],
                'successExpects' => $this->once(),
                'errorExpects' => $this->never(),
                'expectedResult' => Result\Success::class,
            ],
            [
                'resourcesConfig'=> [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
                'successExpects' => $this->once(),
                'errorExpects' => $this->never(),
                'expectedResult' => Result\Success::class,
            ],
            [
                'resourcesConfig'=> [
                    'some_setup' => [
                        'connection' => 'value',
                    ],
                ],
                'successExpects' => $this->once(),
                'errorExpects' => $this->never(),
                'expectedResult' => Result\Success::class,
            ],
            [
                'resourcesConfig'=> [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'some_setup' => [
                        'connection' => 'value',
                    ],
                ],
                'successExpects' => $this->once(),
                'errorExpects' => $this->never(),
                'expectedResult' => Result\Success::class,
            ],
            [
                'resourcesConfig'=> [
                    'default_setup' => [],
                ],
                'successExpects' => $this->never(),
                'errorExpects' => $this->once(),
                'expectedResult' => Result\Error::class,
            ],
            [
                'resourcesConfig'=> [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'some_setup' => [],
                ],
                'successExpects' => $this->never(),
                'errorExpects' => $this->once(),
                'expectedResult' => Result\Error::class,
            ],
        ];
    }
}
