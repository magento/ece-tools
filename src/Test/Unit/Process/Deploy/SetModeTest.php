<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Writer as DeployConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\SetMode;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetModeTest extends TestCase
{
    /**
     * @var SetMode
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DeployConfigWriter|Mock
     */
    private $deployConfigWriterMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->deployConfigWriterMock = $this->createMock(DeployConfigWriter::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new SetMode(
            $this->environmentMock,
            $this->loggerMock,
            $this->shellMock,
            $this->deployConfigWriterMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $mode = Environment::MAGENTO_PRODUCTION_MODE;

        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn($mode);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn(sprintf("Set Magento application mode to '%s'", $mode));
        $this->deployConfigWriterMock->expects($this->once())
            ->method('update')
            ->with(['MAGE_MODE' => 'production']);

        $this->process->execute();
    }

    public function testExecuteDeveloperMode()
    {
        $mode = Environment::MAGENTO_DEVELOPER_MODE;
        $verbosity = '-vvv';

        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn($mode);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn($verbosity);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn(sprintf("Set Magento application mode to '%s'", $mode));
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(sprintf(
                "php ./bin/magento deploy:mode:set %s %s",
                $mode,
                $verbosity
            ));

        $this->process->execute();
    }
}
