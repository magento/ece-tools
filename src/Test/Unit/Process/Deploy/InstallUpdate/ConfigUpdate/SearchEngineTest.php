<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as EnvReader;
use Magento\MagentoCloud\Config\Deploy\Writer as EnvWriter;
use Magento\MagentoCloud\Config\SearchEngine as SearchEngineConfig;
use Magento\MagentoCloud\Config\Shared\Reader as SharedReader;
use Magento\MagentoCloud\Config\Shared\Writer as SharedWriter;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;
use Magento\MagentoCloud\Process\ProcessException;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SearchEngineTest extends TestCase
{
    /**
     * @var SearchEngine
     */
    private $process;

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
    protected function setUp()
    {
        $this->envWriterMock = $this->createMock(EnvWriter::class);
        $this->envReaderMock = $this->createMock(EnvReader::class);
        $this->sharedWriterMock = $this->createMock(SharedWriter::class);
        $this->sharedReaderMock = $this->createMock(SharedReader::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->configMock = $this->createMock(SearchEngineConfig::class);

        $this->process = new SearchEngine(
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
     * @param bool $is21
     * @param InvokedCount $useSharedWriter
     * @param InvokedCount $useSharedReader
     * @param InvokedCount $useEnvWriter
     * @param InvokedCount $useEnvReader
     * @param array $searchConfig
     * @param array $fileConfig
     * @param array $expectedConfig
     * @throws ProcessException
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool $is21,
        InvokedCount $useSharedWriter,
        InvokedCount $useSharedReader,
        InvokedCount $useEnvWriter,
        InvokedCount $useEnvReader,
        array $searchConfig,
        array $fileConfig,
        array $expectedConfig
    ) {
        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($searchConfig);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willReturn($is21);
        $this->sharedReaderMock->expects($useSharedReader)
            ->method('read')
            ->willReturn($fileConfig);
        $this->sharedWriterMock->expects($useSharedWriter)
            ->method('create')
            ->with($expectedConfig);
        $this->envReaderMock->expects($useEnvReader)
            ->method('read')
            ->willReturn($fileConfig);
        $this->envWriterMock->expects($useEnvWriter)
            ->method('create')
            ->with($expectedConfig);

        $this->process->execute();
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider(): array
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
                'useSharedWriter' => $this->once(),
                'useSharedReader' => $this->once(),
                'useEnvWriter' => $this->never(),
                'useEnvReader' => $this->never(),
                'searchConfig' => $mysqlSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $mysqlExpectedConfig
            ],
            'magento version > 2.1 mysql config' => [
                'is21' => false,
                'useSharedWriter' => $this->never(),
                'useSharedReader' => $this->never(),
                'useEnvWriter' => $this->once(),
                'useEnvReader' => $this->once(),
                'searchConfig' => $mysqlSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $mysqlExpectedConfig
            ],
            'magento version 2.1 elasticsearch config' => [
                'is21' => true,
                'useSharedWriter' => $this->once(),
                'useSharedReader' => $this->once(),
                'useEnvWriter' => $this->never(),
                'useEnvReader' => $this->never(),
                'searchConfig' => $elasticSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $elasticExpectedConfig
            ],
            'magento version > 2.1 elasticsearch config' => [
                'is21' => false,
                'useSharedWriter' => $this->never(),
                'useSharedReader' => $this->never(),
                'useEnvWriter' => $this->once(),
                'useEnvReader' => $this->once(),
                'searchConfig' => $elasticSearchConfig,
                'fileConfig' => $fileConfig,
                'expectedConfig' => $elasticExpectedConfig
            ],

        ];
    }

    /**
     * @throws ProcessException
     *
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithException()
    {
        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];

        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
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

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     *
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithPackageException()
    {
        $config['system']['default']['catalog']['search'] = ['engine' => 'mysql'];

        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->configMock->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating search engine configuration.'],
                ['Set search engine to: mysql']
            );
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('2.1.*')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     *
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithConfigException()
    {
        $this->configMock->expects($this->once())
            ->method('getConfig')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->process->execute();
    }
}
