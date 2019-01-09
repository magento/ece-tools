<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\ConfigUpdate\Cache;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CacheTest extends TestCase
{
    use PHPMock;

    /**
     * @var Cache
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var CacheFactory|Mock
     */
    private $cacheConfigMock;

    /**
     * @var MockObject
     */
    private $socketCreateMock;

    /**
     * @var MockObject
     */
    private $socketConnectMock;

    /**
     * @var MockObject
     */
    private $socketCloseMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->cacheConfigMock = $this->createMock(CacheFactory::class);

        $this->process = new Cache(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->cacheConfigMock
        );

        $this->socketCreateMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy\ConfigUpdate',
            'socket_create'
        );
        $this->socketConnectMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy\ConfigUpdate',
            'socket_connect'
        );
        $this->socketCloseMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\PreDeploy\ConfigUpdate',
            'socket_close'
        );
    }

    public function testExecute()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->process->execute();
    }

    public function testExecuteEmptyConfig()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['cache' => [
                'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
            ]]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Cache configuration was not found. Removing cache configuration.');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->process->execute();
    }

    public function testExecuteRedisService()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                ]],
            ]);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                ]],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->process->execute();
    }

    public function testExecuteRedisFailed()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                ]],
            ]);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(false);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Cache is configured for a Redis service that is not available. Configuration will be ignored.');

        $this->process->execute();
    }

    public function testExecuteMixedBackends()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'frontName1' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                    ],
                    'frontName2' => [
                        'backend' => 'cacheDriver'
                    ]
                ],
            ]);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(false);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => ['frontName2' => [
                    'backend' => 'cacheDriver',
                ]],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->process->execute();
    }
}
