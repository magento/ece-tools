<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Cache;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class CacheTest extends TestCase
{
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
     * @var Cache\Config|Mock
     */
    private $cacheConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->cacheConfigMock = $this->createMock(Cache\Config::class);

        $this->process = new Cache(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->cacheConfigMock
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
                'frontend' => ['cache_options'],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => ['cache_options'],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->process->execute();
    }

    public function testExecuteEmptyConfig()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['cache' => [
                'frontend' => ['cache_options'],
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

        $this->process->execute();
    }
}
