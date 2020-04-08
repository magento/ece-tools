<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class LogTest extends TestCase
{
    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @var Repository|MockObject
     */
    private $repositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->repositoryMock = $this->createMock(Repository::class);

        $this->repositoryFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->repositoryMock);

        $this->logConfig = new LogConfig(
            $this->fileListMock,
            $this->readerMock,
            $this->repositoryFactoryMock
        );
    }

    /**
     * @param array $config
     * @param array $expectedResult
     * @dataProvider getHandlersDataProvider
     */
    public function testGetHandlers(array $config, array $expectedResult): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn('somePath');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->assertSame($expectedResult, $this->logConfig->getHandlers());
    }

    /**
     * @return array
     */
    public function getHandlersDataProvider(): array
    {
        return [
            [
                'config' => [],
                'expectedResult' => [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => 'somePath']
                ]
            ],
            [
                'config' => ['someConfig' => ['someConfig']],
                'expectedResult' => [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => 'somePath']
                ],
            ],
            [
                'config' => ['log' => []],
                'expectedResult' => [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => 'somePath']
                ],
            ],
            [
                'config' => ['log' => ['SomeHandler' => ['SomeConfig']]],
                'expectedResult' => [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => 'somePath'],
                    'SomeHandler' => ['SomeConfig']
                ],
            ],
            [
                'config' => ['log' => ['SomeHandler' => ['SomeConfig']], 'someConfig' => ['someConfig']],
                'expectedResult' => [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => 'somePath'],
                    'SomeHandler' => ['SomeConfig']
                ],
            ],
        ];
    }

    public function testGet(): void
    {
        $config = ['log' => ['SomeHandler' => ['SomeConfig']], 'someConfig' => ['someConfig']];
        $logPath = 'var/log/cloud.log';
        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn($logPath);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->assertInstanceOf(
            Repository::class,
            $this->logConfig->get(HandlerFactory::HANDLER_STREAM)
        );
        $this->assertInstanceOf(
            Repository::class,
            $this->logConfig->get(HandlerFactory::HANDLER_FILE)
        );
        $this->assertInstanceOf(
            Repository::class,
            $this->logConfig->get('SomeHandler')
        );
    }

    public function testGetWithException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configuration for SomeHandler is not found');

        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn('somePath');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->logConfig->get('SomeHandler');
    }
}
