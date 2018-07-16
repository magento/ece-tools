<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface as StageConfig;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\S3Bucket;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
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
     * @var DeployConfig|MockObject
     */
    private $deployConfigMock;

    /**
     * @var StageConfig|MockObject
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

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sharedConfigMock = $this->createMock(SharedConfig::class);
        $this->deployConfigMock = $this->createMock(DeployConfig::class);
        $this->stageConfigMock = $this->createMock(StageConfig::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->process = new S3Bucket(
            $this->loggerMock,
            $this->sharedConfigMock,
            $this->deployConfigMock,
            $this->stageConfigMock,
            $this->flagManagerMock
        );
    }

    public function testExecuteSettingsEmpty()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfig::VAR_S3_CONFIGURATION)
            ->willReturn([]);
        $this->deployConfigMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['system.default.thai_s3.general', [], []],
                ['system.default.system.media_storage_configuration.media_storage', []],
            ]);
        $this->deployConfigMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules.Thai_S3')
            ->willReturn(null);

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

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfig::VAR_S3_CONFIGURATION)
            ->willReturn($s3Config);
        $this->deployConfigMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['system.default.thai_s3.general', [], []],
                ['system.default.system.media_storage_configuration.media_storage', []],
                ['system.default.thai_s3.general', [], $s3Config],
            ]);
        $this->deployConfigMock->expects($this->once())
            ->method('set')
            ->with('system.default.thai_s3.general', $s3Config);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating S3 Configuration');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules.Thai_S3')
            ->willReturn(null);

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

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfig::VAR_S3_CONFIGURATION)
            ->willReturn($s3Config);
        $this->deployConfigMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['system.default.thai_s3.general', [], $s3Config],
                ['system.default.system.media_storage_configuration.media_storage', null],
            ]);
        $this->deployConfigMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules.Thai_S3')
            ->willReturn(null);

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

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfig::VAR_S3_CONFIGURATION)
            ->willReturn($s3Config);
        $this->deployConfigMock->expects($this->atLeastOnce())
            ->method('get')
            ->withConsecutive(
                ['system.default.thai_s3.general', []],
                ['system.default.system.media_storage_configuration.media_storage'],
                ['system.default.thai_s3.general', []]
            )->willReturnOnConsecutiveCalls($s3Config, S3Bucket::MEDIA_STORAGE_S3, $s3Config);
        $this->deployConfigMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules.Thai_S3')
            ->willReturn('1');

        $this->process->execute();
    }

    public function testExecuteSetMediaStorage()
    {
        $s3Config = [
            'access_key' => 'AWS_ACCESS_KEY',
            'bucket' => 'my.s3.bucket',
            'region' => 'some-region-1',
            'secret_key' => 'AWS_SECRET_KEY',
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfig::VAR_S3_CONFIGURATION)
            ->willReturn($s3Config);
        $this->deployConfigMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['system.default.thai_s3.general', [], $s3Config],
                ['system.default.system.media_storage_configuration.media_storage', null],
            ]);
        $this->deployConfigMock->expects($this->once())
            ->method('set')
            ->with('system.default.system.media_storage_configuration.media_storage', S3Bucket::MEDIA_STORAGE_S3);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating Media Storage Configuration');
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules.Thai_S3')
            ->willReturn('1');

        $this->process->execute();
    }
}
