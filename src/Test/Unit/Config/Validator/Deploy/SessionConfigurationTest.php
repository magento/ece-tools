<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\SessionConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SessionConfigurationTest extends TestCase
{
    /**
     * @var SessionConfiguration
     */
    private $validator;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->validator = new SessionConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $sessionConfiguration
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $sessionConfiguration, string $expectedResultClass)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($sessionConfiguration);

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
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
}
