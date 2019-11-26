<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\DocumentRoot;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
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
    private $step;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->step = new DocumentRoot(
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

        $this->step->execute();
    }
}
