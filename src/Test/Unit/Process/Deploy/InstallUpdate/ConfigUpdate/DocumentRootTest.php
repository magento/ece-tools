<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\DocumentRoot;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DocumentRootTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var DocumentRoot
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->process = new DocumentRoot(
            $this->loggerMock,
            $this->configWriterMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('The value of the property \'directories/document_root_is_pub\' set as \'true\'');
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['directories' => ['document_root_is_pub' => true]]);

        $this->process->execute();
    }
}
