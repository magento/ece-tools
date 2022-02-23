<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\CleanupDbConfig;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class CleanupDbConfigTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var CleanupDbConfig
     */
    private $step;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);

        $this->step = new CleanupDbConfig(
            $this->loggerMock,
            $this->configWriterMock,
            $this->configReaderMock,
            $this->dbConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithUpdateDbConfig()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => ['host' => 'some.host']]]);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'install' => [],
                'db' => [
                    'connection' => [
                        'default' => ['host' => 'some.host2'],
                        'checkout' => [],
                        'sales' => [],
                    ]
                ],
                'resource' => []
            ]);

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Previous split DB connection will be lost as new custom main connection was set');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);

        $this->step->execute();
    }

    /**
     * @param array $dbConfig
     * @param array $mageConfig
     *
     * @throws StepException
     * @dataProvider dataProviderExecuteInstallUpdateByDefault
     */
    public function testExecuteWithoutUpdateDbConfig(array $dbConfig, array $mageConfig)
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);

        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * @return array
     */
    public function dataProviderExecuteInstallUpdateByDefault(): array
    {
        return [
            'deploy with new installation' => [
                'dbConfig' => [
                    'connection' => [
                        'default' => ['host' => 'default.host'],
                    ],
                ],
                'mageConfig' => [],
            ],
            'redeploy by default' => [
                'dbConfig' => [
                    'connection' => [
                        'default' => ['host' => 'default.host'],
                    ],
                ],
                'mageConfig' => [
                    'db' => [
                        'connection' => [
                            'default' => ['host' => 'default.host'],
                        ],
                    ],
                ],
            ],
            'redeploy with custom db host' => [
                'dbConfig' => [
                    'connection' => [
                        'default' => ['host' => 'custom.host'],
                    ],
                ],
                'mageConfig' => [
                    'db' => [
                        'connection' => [
                            'default' => ['host' => 'default.host'],
                        ],
                    ],
                ],
            ],
            'redeploy with custom split db settings' => [
                'dbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom.host',
                        ],
                    ]
                ],
                'mageConfig' => [
                    'db' => [
                        'connection' => [
                            'default' => ['host' => 'custom.host'],
                            'checkout' => [],
                            'sales' => [],
                        ]
                    ],
                ]
            ]
        ];
    }
}
