<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\CheckEnvVarMageErrorReportDirNestingLevel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CheckEnvVarMageErrorReportDirNestingLevelTest extends TestCase
{
    /**
     * @var CheckEnvVarMageErrorReportDirNestingLevel
     */
    private $processor;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);

        $this->processor = new CheckEnvVarMageErrorReportDirNestingLevel(
            $this->loggerMock,
            $this->environmentMock,
            $this->configFileListMock
        );
    }

    public function testExecuteWithEnvVariable()
    {
        $errorReportConfigFile = 'magento_root/pub/errors/local.xml';
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with(CheckEnvVarMageErrorReportDirNestingLevel::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL)
            ->willReturn(5);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn($errorReportConfigFile);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                sprintf(
                    '%s environment variable detected. %s: %s. '
                    . 'Value of the property \'config.report.dir_nesting_level\' from \'%s\' file be ignored',
                    CheckEnvVarMageErrorReportDirNestingLevel::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                    CheckEnvVarMageErrorReportDirNestingLevel::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                    5,
                    $errorReportConfigFile
                )
            );
        $this->processor->execute();
    }

    public function testExecuteWithoutEnvVariable()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with(CheckEnvVarMageErrorReportDirNestingLevel::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL)
            ->willReturn(null);
        $this->configFileListMock->expects($this->never())
            ->method('getErrorReportConfig');
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->processor->execute();
    }
}
