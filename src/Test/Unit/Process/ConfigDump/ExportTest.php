<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ConfigDump\Export;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ExportTest extends TestCase
{
    /**
     * @var Export
     */
    private $process;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var Reader|Mock
     */
    private $readerMock;

    /**
     * @var Writer|Mock
     */
    private $writerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->writerMock = $this->createMock(Writer::class);

        $this->process = new Export(
            $this->shellMock,
            $this->fileMock,
            $this->readerMock,
            $this->writerMock
        );
    }

    public function testProcess()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento app:config:dump --ansi --no-interaction']
            );
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'some' => 'config',
            ]);
        $this->writerMock->expects($this->once())
            ->method('create')
            ->with([
                'some' => 'config',
            ]);

        $this->process->execute();
    }
}
