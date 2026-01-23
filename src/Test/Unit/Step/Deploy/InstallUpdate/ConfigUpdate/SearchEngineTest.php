<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as EnvReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as EnvWriter;
use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface as SharedReader;
use Magento\MagentoCloud\Config\Magento\Shared\WriterInterface as SharedWriter;
use Magento\MagentoCloud\Config\SearchEngine as SearchEngineConfig;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class SearchEngineTest extends TestCase
{
    /**
     * @var SearchEngine
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var EnvWriter|MockObject
     */
    private $envWriterMock;

    /**
     * @var SharedWriter|MockObject
     */
    private $sharedWriterMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var SearchEngineConfig|MockObject
     */
    private $configMock;

    /**
     * @var EnvReader|MockObject
     */
    private $envReaderMock;

    /**
     * @var SharedReader|MockObject
     */
    private $sharedReaderMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->envWriterMock = $this->createMock(EnvWriter::class);
        $this->envReaderMock = $this->createMock(EnvReader::class);
        $this->sharedWriterMock = $this->createMock(SharedWriter::class);
        $this->sharedReaderMock = $this->createMock(SharedReader::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->configMock = $this->createMock(SearchEngineConfig::class);

        $this->step = new SearchEngine(
            $this->loggerMock,
            $this->envWriterMock,
            $this->envReaderMock,
            $this->sharedWriterMock,
            $this->sharedReaderMock,
            $this->magentoVersionMock,
            $this->configMock
        );
    }

    /**
     * Test execute method.
     *
     * @param bool $is21
     * @param $useSharedWriter
     * @param $useSharedReader
     * @param $useEnvWriter
     * @param $useEnvReader
     * @param array $searchConfig
     * @param array $fileConfig
     * @param array $expectedConfig
     * @dataProvider executeDataProvider
     * @return void
     * @throws \ReflectionException
     * @throws StepException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        bool $is21,
        bool $useSharedWriter,
        bool $useSharedReader,
        bool $useEnvWriter,
        bool $useEnvReader,
        array $searchConfig,
        array $fileConfig,
        array $expectedConfig
    ): void {
        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($searchConfig);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating search engine configuration.',
                    'Set search engine to: mysql'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn($is21);
        $this->sharedReaderMock->expects($useSharedReader ? $this->once() : $this->never())
            ->method('read')
            ->willReturn($fileConfig);
        $this->sharedWriterMock->expects($useSharedWriter ? $this->once() : $this->never())
            ->method('create')
            ->with($expectedConfig);
        $this->envReaderMock->expects($useEnvReader ? $this->once() : $this->never())
            ->method('read')
            ->willReturn($fileConfig);
        $this->envWriterMock->expects($useEnvWriter ? $this->once() : $this->never())
            ->method('create')
            ->with($expectedConfig);

        $this->step->execute();
    }

    /**
     * Execute data provider method.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function executeDataProvider(): array
    {
        $mysqlSearchConfig['system']['default']['catalog']['search'] = ['engine' => 'mysql'];
        $elasticSearchConfig = [
            'system' => [
                'default' => [
                    'smile_elasticsuite_core_base_settings' => [
                        'option3' => 'value3',
                        'option4' => 'value4'
                    ],
                    'catalog' => [
                        'search' => [
                            'engine' => 'elasticsearch5',
                            'elasticsearh5_host' => 'localhost',
                            'elasticsearh5_port' => '9200',
                        ]
                    ]
                ]
            ]
        ];
        $fileConfig = [
            'config' => 'value',
            'system' => [
                'default' => [
                    'smile_elasticsuite_core_base_settings' => [
                        'option1' => 'value1',
                        'option2' => 'value2'
                    ],
                    'category' => [
                        'option' => 'value'
                    ],
                    'catalog' => [
                        'search' => [
                            'engine' => 'elasticsearch',
                            'elasticsearh_host' => 'localhost',
                            'elasticsearh_port' => '9200',
                        ]
                    ]
                ],
                'store1' => [
                    'category' => [
                        'option' => 'value'
                    ],
                ],
            ]
        ];
        $mysqlExpectedConfig = [
            'config' => 'value',
            'system' => [
                'default' => [
                    'catalog' => [
                        'search' => [
                            'engine' => 'mysql'
                        ],
                    ],
                    'category' => [
                        'option' => 'value'
                    ],
                ],
                'store1' => [
                    'category' => [
                        'option' => 'value'
                    ],
                ],
            ]
        ];
        $elasticExpectedConfig = [
            'config' => 'value',
            'system' => [
                'default' => [
                    'smile_elasticsuite_core_base_settings' => [
                        'option3' => 'value3',
                        'option4' => 'value4'
                    ],
                    'category' => [
                        'option' => 'value'
                    ],
                    'catalog' => [
                        'search' => [
                            'engine' => 'elasticsearch5',
                            'elasticsearh5_host' => 'localhost',
                            'elasticsearh5_port' => '9200',
                        ]
                    ]
                ],
                'store1' => [
                    'category' => [
                        'option' => 'value'
                    ],
                ],
            ]
        ];

        return [
            'magento version 2.1 mysql config' => [
                'is21' => true,
                'useSharedWriter' => true,
                'useSharedReader' => true,
                'useEnvWriter' => false,
                'useEnvReader' => false,
                'searchConfig' => $mysqlSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $mysqlExpectedConfig
            ],
            'magento version > 2.1 mysql config' => [
                'is21' => false,
                'useSharedWriter' => false,
                'useSharedReader' => false,
                'useEnvWriter' => true,
                'useEnvReader' => true,
                'searchConfig' => $mysqlSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $mysqlExpectedConfig
            ],
            'magento version 2.1 elasticsearch config' => [
                'is21' => true,
                'useSharedWriter' => true,
                'useSharedReader' => true,
                'useEnvWriter' => false,
                'useEnvReader' => false,
                'searchConfig' => $elasticSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $elasticExpectedConfig
            ],
            'magento version > 2.1 elasticsearch config' => [
                'is21' => false,
                'useSharedWriter' => false,
                'useSharedReader' => false,
                'useEnvWriter' => true,
                'useEnvReader' => true,
                'searchConfig' => $elasticSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $elasticExpectedConfig
            ],

        ];
    }

    /**
     * Test execute with exception method.
     *
     * @return void
     * @throws \ReflectionException
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];

        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating search engine configuration.',
                    'Set search engine to: mysql'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn(false);
        $this->sharedWriterMock->expects($this->never())
            ->method('update')
            ->with($config);
        $this->envWriterMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willThrowException(new FileSystemException('Some error'));

        $this->step->execute();
    }

    /**
     * Test execute with package exception method.
     *
     * @return void
     * @throws \ReflectionException
     * @throws StepException
     */
    public function testExecuteWithPackageException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];

        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Updating search engine configuration.',
                    'Set search engine to: mysql'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->step->execute();
    }

    /**
     * Test execute with config exception method.
     *
     * @return void
     * @throws \ReflectionException
     * @throws StepException
     */
    public function testExecuteWithConfigException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->step->execute();
    }
}
