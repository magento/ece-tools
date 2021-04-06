<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\SplitDb;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\ConfigException;

/**
 * Test for SplitDb class
 */
class SplitDbTest extends TestCase
{
    /**
     * @var SplitDb
     */
    private $splitDb;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->splitDb = new SplitDb($this->stageConfigMock);
    }

    /**
     * @param array $splitDb
     * @param array $dbConfig
     * @param bool $expectedResult
     * @throws ConfigException
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $splitDb, array $dbConfig, bool $expectedResult)
    {
        $this->stageConfigMock->expects($this->atLeast(1))
            ->method('get')
            ->willReturnOnConsecutiveCalls($splitDb, $dbConfig);

        $this->assertEquals($expectedResult, $this->splitDb->isConfigured());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                [],
                [],
                false
            ],
            [
                [],
                [
                    'connection' => [
                        'default' => [
                            'host' => 'some_host'
                        ]
                    ]
                ],
                false
            ],
            [
                ['quote', 'sales'],
                [],
                true
            ],
            [
                [],
                [
                    'connection' => [
                        'default' => [
                            'host' => 'some_host'
                        ],
                        'sales' => [
                            'host' => 'some_host'
                        ],
                    ]
                ],
                true
            ],
            [
                [],
                [
                    'connection' => [
                        'default' => [
                            'host' => 'some_host'
                        ],
                        'quote' => [
                            'host' => 'some_host'
                        ],
                    ]
                ],
                true
            ],
        ];
    }
}
