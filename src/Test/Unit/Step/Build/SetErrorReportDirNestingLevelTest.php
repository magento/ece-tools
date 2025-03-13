<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Build\SetReportDirNestingLevel;
use Magento\MagentoCloud\Step\StepException;
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
     * @var SetReportDirNestingLevel
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

    /**
     * The path to the error report configuration file
     * @var string
     */
    private $configFile = 'magento_root/pub/errors/local.xml';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Configuring directory nesting level for saving error reports');
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn($this->configFile);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->fileMock = $this->createMock(File::class);

        $this->processor = new SetReportDirNestingLevel(
            $this->loggerMock,
            $this->configFileListMock,
            $this->stageConfigMock,
            $this->fileMock
        );
    }

    /**
     * The case the file <magento_root>/errors/local.xml exists
     */
    public function testExecuteLocalXmlExists()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($this->configFile)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(sprintf(
                'The error reports configuration file `%s` exists.'
                .' Value of the property `%s` of .magento.env.yaml will be ignored',
                $this->configFile,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ));
        $this->processor->execute();
    }

    /**
     * The case the file <magento_root>/pub/errors/local.xml not exist
     */
    public function testExecuteLocalXmlNotExist()
    {
        $value = 4;
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($this->configFile)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL)
            ->willReturn($value);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->configFile,
                <<<XML
<?xml version="1.0"?>
<config>
    <report>
        <dir_nesting_level>{$value}</dir_nesting_level>
    </report>
</config> 
XML
            );
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                sprintf(
                    'The file %s with the `config.report.dir_nesting_level` property: `%s` was created.',
                    $this->configFile,
                    $value
                )
            );
        $this->processor->execute();
    }

    public function testExecuteWithConfigException()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('some error', Error::BUILD_CONFIG_NOT_DEFINED));

        $this->expectExceptionCode(Error::BUILD_CONFIG_NOT_DEFINED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->processor->execute();
    }

    public function testExecuteWithFileSystemException()
    {
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->willThrowException(new FileSystemException('some error'));

        $this->expectExceptionCode(Error::BUILD_FILE_LOCAL_XML_IS_NOT_WRITABLE);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->processor->execute();
    }
}
