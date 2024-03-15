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
     * Path to error report config file
     * @var string
     */
    private $reportConfigFile = '<magento_root>/pub/errors/local.xml';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->configFileListMock->expects($this->once())
            ->method('getErrorReportConfig')
            ->willReturn($this->reportConfigFile);
        $this->fileMock = $this->createMock(File::class);
        $this->encoderMock = $this->createMock(XmlEncoder::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new ReportDirNestingLevel(
            $this->environmentMock,
            $this->configFileListMock,
            $this->fileMock,
            $this->encoderMock,
            $this->resultFactoryMock
        );
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL exists
     */
    public function testValidateWithEnvVar()
    {
        $someValue = 'some value';
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn($someValue);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('valid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('valid xml')
            ->willReturn(['report' => ['dir_nesting_level' => $someValue]]);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     *  The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     *  but the property config.report.dir_nesting_level of the file <magento_root>/errors/local.xml exists
     */
    public function testValidateWithoutEnvVarAndWithConfigValue()
    {
        $someValue = 'some value';
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('valid xml');
        $this->encoderMock->expects($this->once())
            ->method('decode')
            ->with('valid xml')
            ->willReturn(['report' => ['dir_nesting_level' => $someValue]]);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and the file <magento_root>/errors/local.xml exists,
     * but without property config.report.dir_nesting_level
     */
    public function testValidateWithoutEnvVarWithoutConfigValue()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
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
                'The directory nesting level value for error reporting has not been configured.',
                'You can configure the setting using the `config.report.dir_nesting_level` variable'
                . ' in the file ' . $this->reportConfigFile
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and a content of the file <magento_root>/errors/local.xml is invalid
     */
    public function testValidateWithoutEnvVarWithInvalidConfigFile()
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
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
                sprintf('Invalid configuration in the %s file. Invalid xml', $this->reportConfigFile),
                'Fix the directory nesting level configuration for error reporting in the file '
                . $this->reportConfigFile
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * The case when the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist
     * and the file <magento_root>/errors/local.xml not exist too
     */
    public function testValidateWithoutEnvVarWithoutConfigFile()
    {
        $message = sprintf('File %s not found', $this->reportConfigFile);
        $this->environmentMock->expects($this->once())
            ->method('getEnvVarMageErrorReportDirNestingLevel')
            ->willReturn(false);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException($message));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($message);

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
