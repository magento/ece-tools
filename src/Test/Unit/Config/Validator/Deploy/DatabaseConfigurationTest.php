<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\DatabaseConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DatabaseConfigurationTest extends TestCase
{
    /**
     * @var DatabaseConfiguration
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

        $this->validator = new DatabaseConfiguration(
            $this->resultFactoryMock,
            $this->stageConfigMock
        );
    }

    /**
     * @param array $dbConfiguration
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $dbConfiguration, string $expectedResultClass)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_DATABASE_CONFIGURATION)
            ->willReturn($dbConfiguration);

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
