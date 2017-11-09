<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Log as LogConfig;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class LogTest extends TestCase
{
    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var Reader|Mock
     */
    private $readerMock;

    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->readerMock = $this->createMock(Reader::class);

        $this->logConfig = new LogConfig($this->fileListMock, $this->readerMock);
    }

    /**
     * @param array $config
     * @param array $expectedResult
     * @dataProvider getHandlersDataProvider
     */
    public function testGetHandlers(array $config, array $expectedResult)
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
    public function getHandlersDataProvider()
    {
        return [
            ['config' => [], 'expectedResult' => [HandlerFactory::HANDLER_STREAM, HandlerFactory::HANDLER_FILE]],
            [
                'config' => ['someConfig' => ['someConfig']],
                'expectedResult' => [HandlerFactory::HANDLER_STREAM, HandlerFactory::HANDLER_FILE]
            ],
            [
                'config' => ['log' => []],
                'expectedResult' => [HandlerFactory::HANDLER_STREAM, HandlerFactory::HANDLER_FILE]
            ],
            [
                'config' => ['log' => ['SomeHandler' => ['SomeConfig']]],
                'expectedResult' => [HandlerFactory::HANDLER_STREAM, HandlerFactory::HANDLER_FILE, 'SomeHandler']
            ],
            [
                'config' => ['log' => ['SomeHandler' => ['SomeConfig']], 'someConfig' => ['someConfig']],
                'expectedResult' => [HandlerFactory::HANDLER_STREAM, HandlerFactory::HANDLER_FILE, 'SomeHandler']
            ],
        ];
    }

    public function testGet()
    {
        $config = ['log' => ['SomeHandler' => ['SomeConfig']], 'someConfig' => ['someConfig']];
        $logPath = 'var/log/cloud.log';
        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn($logPath);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->assertEquals(
            (new Repository(['stream' => 'php://stdout'])),
            $this->logConfig->get(HandlerFactory::HANDLER_STREAM)
        );
        $this->assertEquals(
            (new Repository(['stream' => $logPath])),
            $this->logConfig->get(HandlerFactory::HANDLER_FILE)
        );
        $this->assertEquals(
            (new Repository(['SomeConfig'])),
            $this->logConfig->get('SomeHandler')
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Configuration for SomeHandler is not found
     */
    public function testGetWithException()
    {
        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn('somePath');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->logConfig->get('SomeHandler');
    }
}
