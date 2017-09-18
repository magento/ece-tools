<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Writer as DeployConfigWriter;
use Magento\MagentoCloud\Process\Deploy\CreateConfigFile;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class CreateConfigFileTest extends TestCase
{
    /**
     * @var CreateConfigFile
     */
    private $process;

    /**
     * @var DeployConfigWriter|Mock
     */
    private $deployConfigWriterMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->deployConfigWriterMock = $this->createMock(DeployConfigWriter::class);

        $this->process = new CreateConfigFile(
            $this->deployConfigWriterMock
        );
    }

    public function testExecute()
    {
        $this->deployConfigWriterMock->expects($this->once())
            ->method('update')
            ->willReturn([]);

        $this->process->execute();
    }
}
