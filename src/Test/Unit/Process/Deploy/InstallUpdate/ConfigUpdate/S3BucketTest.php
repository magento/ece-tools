<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface as DeployConfig;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\S3Bucket;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test S3Bucket config update process.
 */
class S3BucketTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SharedConfig|MockObject
     */
    private $sharedConfigMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var DeployConfig|MockObject
     */
    private $stageConfigMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var S3Bucket
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sharedConfigMock = $this->createMock(SharedConfig::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->stageConfigMock = $this->createMock(DeployConfig::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->process = new S3Bucket(
            $this->loggerMock,
            $this->sharedConfigMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->stageConfigMock,
            $this->flagManagerMock
        );
    }

    public function testExecuteSettingsEmpty()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo(DeployConfig::VAR_S3_CONFIGURATION))
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(null);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);

        $this->process->execute();
    }

    public function testExecuteConfigChanged()
    {
        $s3Config = [
            'access_key' => 'AWS_ACCESS_KEY',
            'bucket' => 'my.s3.bucket',
            'region' => 'some-region-1',
            'secret_key' => 'AWS_SECRET_KEY',
        ];

        $envConfig = [
            'system' => [
                'default' => [
                    'thai_s3' => [
                        'general' => $s3Config,
                    ]
                ]
            ]
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo(DeployConfig::VAR_S3_CONFIGURATION))
            ->willReturn($s3Config);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating S3 Configuration');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(null);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($envConfig);

        $this->process->execute();
    }

    public function testExecuteModuleDisabled()
    {
        $s3Config = [
            'access_key' => 'AWS_ACCESS_KEY',
            'bucket' => 'my.s3.bucket',
            'region' => 'some-region-1',
            'secret_key' => 'AWS_SECRET_KEY',
        ];

        $envConfig = [
            'system' => [
                'default' => [
                    'thai_s3' => [
                        'general' => $s3Config,
                    ]
                ]
            ]
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo(DeployConfig::VAR_S3_CONFIGURATION))
            ->willReturn($s3Config);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '0']);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($envConfig);

        $this->process->execute();
    }

    public function testConfigurationAllSet()
    {
        $s3Config = [
            'access_key' => 'AWS_ACCESS_KEY',
            'bucket' => 'my.s3.bucket',
            'region' => 'some-region-1',
            'secret_key' => 'AWS_SECRET_KEY',
        ];

        $envConfig = [
            'system' => [
                'default' => [
                    'system' => [
                        'media_storage_configuration' => [
                            'media_storage' => S3Bucket::MEDIA_STORAGE_S3,
                        ],
                    ],
                    'thai_s3' => [
                        'general' => $s3Config,
                    ],
                ],
            ],
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo(DeployConfig::VAR_S3_CONFIGURATION))
            ->willReturn($s3Config);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '1']);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($envConfig);

        $this->process->execute();
    }

    public function testConfigurationSetMediaStorage()
    {
        $s3Config = [
            'access_key' => 'AWS_ACCESS_KEY',
            'bucket' => 'my.s3.bucket',
            'region' => 'some-region-1',
            'secret_key' => 'AWS_SECRET_KEY',
        ];
        $thaiConfig = ['general' => $s3Config];
        $storageConfig = [
            'media_storage_configuration' => [
                'media_storage' => S3Bucket::MEDIA_STORAGE_S3,
            ],
        ];
        $envConfigFinal = [
            'system' => [
                'default' => [
                    'system' => $storageConfig,
                    'thai_s3' => $thaiConfig,
                ],
            ],
        ];
        $envConfigInit = [
            'system' => [
                'default' => [
                    'thai_s3' => $thaiConfig,
                ],
            ],
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo(DeployConfig::VAR_S3_CONFIGURATION))
            ->willReturn($s3Config);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfigInit);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with($this->equalTo(FlagManager::FLAG_S3_CONFIG_MODIFIED));
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating Media Storage Configuration');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '1']);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($envConfigFinal);

        $this->process->execute();
    }
}
