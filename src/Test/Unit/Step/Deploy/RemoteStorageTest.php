<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\Deploy\RemoteStorage;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\RemoteStorage as Config;
use Psr\Log\LoggerInterface;

/**
 * @see RemoteStorage
 */
class RemoteStorageTest extends TestCase
{
    /**
     * @var RemoteStorage
     */
    private $step;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);

        $this->step = new RemoteStorage(
            $this->configMock,
            $this->magentoVersionMock,
            $this->loggerMock,
            $this->writerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
            ]);
        $this->writerMock->expects(self::once())
            ->method('update')
            ->with([
                'remote_storage' => [
                    'driver' => 'adapter',
                    'config' => [
                        'bucket' => 'test_bucket',
                        'region' => 'test_region'
                    ]
                ]
            ]);
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage driver set to: "adapter"');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithKeys(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret'
            ]);
        $this->writerMock->expects(self::once())
            ->method('update')
            ->with([
                'remote_storage' => [
                    'driver' => 'adapter',
                    'config' => [
                        'bucket' => 'test_bucket',
                        'region' => 'test_region',
                        'credentials' => [
                            'key' => 'test_key',
                            'secret' => 'test_secret'
                        ]
                    ]
                ]
            ]);
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage driver set to: "adapter"');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithKeysAndPrefix(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('adapter');
        $this->configMock->method('getPrefix')
            ->willReturn('test_prefix');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret',
            ]);
        $this->writerMock->expects(self::once())
            ->method('update')
            ->with([
                'remote_storage' => [
                    'driver' => 'adapter',
                    'config' => [
                        'bucket' => 'test_bucket',
                        'region' => 'test_region',
                        'credentials' => [
                            'key' => 'test_key',
                            'secret' => 'test_secret'
                        ],
                        'prefix' => 'test_prefix'
                    ]
                ]
            ]);
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage driver set to: "adapter"');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteDisable(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('');
        $this->writerMock->expects(self::once())
            ->method('update')
            ->with(['remote_storage' => ['driver' => 'file']]);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret'
            ]);
        $this->writerMock->expects(self::once())
            ->method('update')
            ->willThrowException(new FileSystemException('Some error'));
        $this->loggerMock->expects(self::once())
            ->method('critical')
            ->with('Some error');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithMissingOptions(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Bucket and region are required configurations');

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.4.2')
            ->willReturn(true);
        $this->configMock->method('getDriver')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'key' => 'test_key',
                'secret' => 'test_secret',
            ]);

        $this->step->execute();
    }
}
