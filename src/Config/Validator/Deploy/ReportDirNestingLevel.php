<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Validates the value of the directories nesting level for error reporting
 */
class ReportDirNestingLevel implements ValidatorInterface
{
    /**
     * The environment variable for controlling the directories nesting level for error reporting
     */
    const ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL = 'MAGE_ERROR_REPORT_DIR_NESTING_LEVEL';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var XmlEncoder
     */
    private $encoder;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param Environment $environment
     * @param ConfigFileList $configFileList
     * @param File $file
     * @param XmlEncoder $encoder
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        ConfigFileList $configFileList,
        File $file,
        XmlEncoder $encoder,
        ResultFactory $resultFactory
    )
    {
        $this->environment = $environment;
        $this->configFileList = $configFileList;
        $this->file = $file;
        $this->encoder = $encoder;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $envVarReportDirNestingLevel = $this->environment->getEnv(self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL);
        $reportConfigFile = $this->configFileList->getErrorReportConfig();
        $envVarReportDirNestingLevelMsg = $envVarReportDirNestingLevel
            ? sprintf(
                'The environment variable `%s` with value `%s` is used to configure '
                . 'the directories nesting level for error reporting.',
                self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                $envVarReportDirNestingLevel
            )
            : '';
        try {
            $errorReportConfig = $this->encoder->decode(
                $this->file->fileGetContents($reportConfigFile),
                XmlEncoder::FORMAT)
                ?: [];
            $reportConfigDirNestingLevel = $errorReportConfig['config']['report']['dir_nesting_level'] ?? null;
            if ($reportConfigDirNestingLevel && $envVarReportDirNestingLevel) {
                return $this->resultFactory->error(
                    sprintf(
                        'Value of `config.report.dir_nesting_level` defined in %s will be ignored. ',
                        $reportConfigFile
                    ) . $envVarReportDirNestingLevelMsg
                );
            } elseif ($envVarReportDirNestingLevel) {
                return $this->resultFactory->error($envVarReportDirNestingLevelMsg);
            } elseif ($reportConfigDirNestingLevel) {
                return $this->resultFactory->success();
            } else {
                return $this->resultFactory->error(
                    'The directories nesting level for error reporting not set.',
                    'For set the directories nesting level use setting '
                    . '`config.report.dir_nesting_level` in the file ' . $reportConfigFile
                );
            }
        } catch (FileSystemException $exception) {
            $msg = $exception->getMessage();
            $msg .= $envVarReportDirNestingLevelMsg ? ' '.$envVarReportDirNestingLevelMsg: '';
            return $this->resultFactory->error($msg);
        } catch (NotEncodableValueException $exception) {
            $msg = $exception->getMessage();
            $msg .= $envVarReportDirNestingLevelMsg ? ' '.$envVarReportDirNestingLevelMsg: '';
            return $this->resultFactory->error($msg);
        }
    }
}
