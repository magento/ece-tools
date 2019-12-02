<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\PostDeploy\EnableCron;
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
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);

        $this->step = new EnableCron(
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
        $this->step->execute();
    }
}
