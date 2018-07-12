<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\UploadStaticContent;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test UploadStaticContent process.
 */
class UploadStaticContentTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SharedConfig|MockObject
     */
    private $configMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var UploadStaticContent
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configMock = $this->createMock(SharedConfig::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->process = new UploadStaticContent(
            $this->loggerMock,
            $this->configMock,
            $this->configReaderMock,
            $this->flagManagerMock,
            $this->shellMock
        );
    }

    public function testModuleNotSet()
    {
        $envConfig = [
            'system' => [
                'default' => [
                    'thai_s3' => [
                        'general' => [
                            'S3 Bucket Configuration',
                        ]
                    ]
                ]
            ]
        ];

        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(null);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('S3 Module is not enabled or config has not been set.');
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->flagManagerMock->expects($this->never())
            ->method('delete');

        $this->process->execute();
    }

    public function testConfigNotSet()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '1']);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('S3 Module is not enabled or config has not been set.');
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->flagManagerMock->expects($this->never())
            ->method('delete');

        $this->process->execute();
    }

    public function testFlagNotSet()
    {
        $envConfig = [
            'system' => [
                'default' => [
                    'thai_s3' => [
                        'general' => [
                            'S3 Bucket Configuration',
                        ]
                    ]
                ]
            ]
        ];

        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '1']);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('delete');
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('S3 configuration has not been changed.');
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecute()
    {
        $envConfig = [
            'system' => [
                'default' => [
                    'thai_s3' => [
                        'general' => [
                            'S3 Bucket Configuration',
                        ]
                    ]
                ]
            ]
        ];

        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Thai_S3' => '1']);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED)
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_S3_CONFIG_MODIFIED);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Uploading static content to S3 bucket.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento s3:storage:export --ansi --no-interaction');

        $this->process->execute();
    }
}
