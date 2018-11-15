<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Process\PostDeploy\EnableCron;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Magento\MagentoCloud\Process\Deploy\EnableCron
 */
class EnableCronTest extends TestCase
{
    /**
     * @var EnableCron
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var Writer|MockObject
     */
    private $writerMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->writerMock = $this->createMock(Writer::class);

        $this->process = new EnableCron(
            $this->loggerMock,
            $this->writerMock,
            $this->readerMock
        );
    }

    public function testExecute()
    {
        $config = ['cron' => ['enabled' => 0, 'other_cron_config' => 1], 'other_conf' => 'value'];
        $configResult = ['cron' => ['other_cron_config' => 1], 'other_conf' => 'value'];
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enable cron');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->writerMock->expects($this->once())
            ->method('create')
            ->with($configResult);
        $this->process->execute();
    }
}
