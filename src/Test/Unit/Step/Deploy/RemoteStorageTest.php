<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->step = new RemoteStorage(
            $this->configMock,
            $this->magentoShellMock,
            $this->magentoVersionMock,
            $this->loggerMock
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
        $this->configMock->method('getAdapter')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
            ]);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with('remote-storage:enable adapter test_bucket test_region');
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage with driver "adapter" was enabled');

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
        $this->configMock->method('getAdapter')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret'
            ]);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with(
                'remote-storage:enable adapter test_bucket test_region'
                . ' --access-key=test_key --secret-key=test_secret'
            );
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage with driver "adapter" was enabled');

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
        $this->configMock->method('getAdapter')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret',
                'prefix' => 'test_prefix'
            ]);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with(
                'remote-storage:enable adapter test_bucket test_region test_prefix'
                . ' --access-key=test_key --secret-key=test_secret'
            );
        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Remote storage with driver "adapter" was enabled');

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
        $this->configMock->method('getAdapter')
            ->willReturn('');
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->with(
                'remote-storage:disable'
            );

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
        $this->configMock->method('getAdapter')
            ->willReturn('adapter');
        $this->configMock->method('getConfig')
            ->willReturn([
                'bucket' => 'test_bucket',
                'region' => 'test_region',
                'key' => 'test_key',
                'secret' => 'test_secret',
                'prefix' => 'test_prefix'
            ]);
        $this->magentoShellMock->expects(self::once())
            ->method('execute')
            ->willThrowException(new ShellException('Some error'));
        $this->loggerMock->expects(self::once())
            ->method('critical')
            ->with('Some error');

        $this->step->execute();
    }
}
