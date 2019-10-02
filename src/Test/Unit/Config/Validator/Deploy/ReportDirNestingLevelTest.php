<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\ReportDirNestingLevel;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * @inheritdoc
 */
class ReportDirNestingLevelTest extends TestCase
{
    /**
     * @var ReportDirNestingLevel
     */
    private $validator;

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

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var XmlEncoder|MockObject
     */
    private $encoderMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->encoderMock = $this->createMock(XmlEncoder::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new ReportDirNestingLevel(
            $this->loggerMock,
            $this->environmentMock,
            $this->configFileListMock,
            $this->fileMock,
            $this->encoderMock,
            $this->resultFactoryMock
        );
    }

    /**
     * The case when the value of the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL is valid
     *
     * @param string|int $envVarValue
     * @dataProvider dataProviderValidateWithValidEnvVar
     */
    public function testValidateWithValidEnvVar($envVarValue)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn($envVarValue);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                sprintf(
                    'The environment variable `MAGE_ERROR_REPORT_DIR_NESTING_LEVEL` with the value `%s`'
                    . ' is used to configure the directories nesting level for error reporting. The environment'
                    . ' variable has a higher priority.  Value of the property `config.report.dir_nesting_level` in'
                    . ' the file <magento_root>/errors/local.xml will be ignored.',
                    $envVarValue
                )
            );

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * DataProvider for testValidateWithValidEnvVar
     *
     * @return array
     */
    public function dataProviderValidateWithValidEnvVar()
    {
        return [
            [0],
            [16],
            [32],
            ["0"],
            ["16"],
            ["32"],
        ];
    }

    /**
     * The case when the value of the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL is invalid
     *
     * @param string|integer $envVarValue
     * @dataProvider dataProviderValidateWithInvalidEnvVar
     */
    public function testValidateWithInvalidEnvVar($envVarValue)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn($envVarValue);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'The value `%s` of the environment variable `MAGE_ERROR_REPORT_DIR_NESTING_LEVEL` is invalid.',
                    $envVarValue
                ),
                'The value of the environment variable `MAGE_ERROR_REPORT_DIR_NESTING_LEVEL` must be an integer'
                . ' between 0 and 32'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * DataProvider for testValidateWithInvalidEnvVar
     *
     * @return array
     */
    public function dataProviderValidateWithInvalidEnvVar()
    {
        return [
            ['invalid-value'],
            [-1],
            ["-1"],
            [35],
            ["35"],
            ["3d"],
            ["d3"],
        ];
    }

    /**
     *  The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     *  and the property config.report.dir_nesting_level of the file <magento_root>/errors/local.xml is valid
     *
     * @param $reportConfigDirNestingLevel
     * @dataProvider dataProviderValidateWithoutEnvVarWithValidReportConfig
     */
    public function testValidateWithoutEnvVarAndWithValidReportConfigDirNestingLevel($reportConfigDirNestingLevel)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('valid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('valid xml')
            ->willReturn(['report' => ['dir_nesting_level' => $reportConfigDirNestingLevel]]);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(sprintf(
                'The property `config.report.dir_nesting_level` defined in the file '
                . '<magento_root>/errors/local.xml with value '
                . '`%s` and  used to configure the directories nesting level for error reporting.',
                $reportConfigDirNestingLevel
            ));

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * DataProvider for testValidateWithoutEnvVarAndWithValidReportConfigDirNestingLevel
     *
     * @return array
     */
    public function dataProviderValidateWithoutEnvVarWithValidReportConfig()
    {
        return [
            [0],
            [16],
            [32],
            ["0"],
            ["16"],
            ["32"],
        ];
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and the property config.report.dir_nesting_level of the file <magento_root>/errors/local.xml is invalid
     *
     * @param $reportConfigDirNestingLevel
     * @dataProvider dataProviderValidateWithoutEnvVarWithInvalidReportConfig
     */
    public function testValidateWithoutEnvVarWithInvalidReportConfig($reportConfigDirNestingLevel)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('valid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('valid xml')
            ->willReturn(['report' => ['dir_nesting_level' => $reportConfigDirNestingLevel]]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'The value `%s` of the property `config.report.dir_nesting_level` '
                    . 'defined in <magento_root>/pub/errors/local.xml is invalid',
                    $reportConfigDirNestingLevel
                ),
                'The value of the property `config.report.dir_nesting_level` must be an integer between 0 and 32'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * DataProvider for testValidateWithoutEnvVarWithInvalidReportConfig
     *
     * @return array
     */
    public function dataProviderValidateWithoutEnvVarWithInvalidReportConfig()
    {
        return [
            ['invalid-value'],
            [-1],
            ["-1"],
            [35],
            ["35"],
            ["3d"],
            ["d3"],
        ];
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and the file <magento_root>/errors/local.xml not exist too
     */
    public function testValidateWithoutEnvVarWithoutReportConfigFile()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException('File <magento_root>/errors/local.xml not found'));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('File <magento_root>/errors/local.xml not found');

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and a content of the file <magento_root>/errors/local.xml is invalid
     */
    public function testValidateWithoutEnvVarWithInvalidContentReportConfigFile()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('invalid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('invalid xml')
            ->willThrowException(new NotEncodableValueException("Invalid xml"));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Config of the file <magento_root>/errors/local.xml is invalid. Invalid xml',
                'Fix the configuration of the directories nesting level for error reporting '
                . 'in the file <magento_root>/errors/local.xml'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and the file <magento_root>/errors/local.xml exists,
     * but without property config.report.dir_nesting_level
     *
     */
    public function testValidateWithoutEnvVarWithoutReportConfigFileDirNestingLevel()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn('<magento_root>/errors/local.xml');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('valid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('valid xml')
            ->willReturn([]);

        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The directories nesting level for error reporting not set.',
                'For set the directories nesting level use setting '
                . '`config.report.dir_nesting_level` in the file <magento_root>/errors/local.xml'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
