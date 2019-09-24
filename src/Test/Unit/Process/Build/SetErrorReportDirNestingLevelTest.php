<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\SetErrorReportDirNestingLevel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetErrorReportDirNestingLevelTest extends TestCase
{
    /**
     * @var SetErrorReportDirNestingLevel
     */
    private $processor;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->fileMock = $this->createMock(File::class);

        $this->processor = new SetErrorReportDirNestingLevel(
            $this->loggerMock,
            $this->configFileListMock,
            $this->stageConfigMock,
            $this->fileMock
        );
    }

    public function testExecuteLocalXmlExists()
    {
        $errorReportConfigFile = 'magento_root/pub/errors/local.xml';
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Configuring directory nesting for saving error reports');
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn($errorReportConfigFile);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($errorReportConfigFile)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(sprintf(
                'Error reports configuration file \'%s exists\'.'
                . 'Value of the property \'%s\' of .magento.env.yaml be ignored',
                $errorReportConfigFile,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ));
        $this->processor->execute();
    }

    public function testExecuteLocalXmlNotExist()
    {
        $errorReportConfigFile = 'magento_root/pub/errors/local.xml';
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Configuring directory nesting for saving error reports');
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn($errorReportConfigFile);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($errorReportConfigFile)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL)
            ->willReturn(4);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $errorReportConfigFile,
                <<<XML
<?xml version="1.0"?>
<config>
    <report>
        <dir_nesting_level>4</dir_nesting_level>
    </report>
</config> 
XML
            );
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                sprintf(
                    'The file %s with property \'config.report.dir_nesting_level\': %s was created.',
                    $errorReportConfigFile,
                    4
                )
            );
        $this->processor->execute();
    }
}
