<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ValidatorException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Psr\Log\LoggerInterface;

/**
 * Validates a value of the directories nesting level for error reporting
 */
class ReportDirNestingLevel implements ValidatorInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ConfigFileList $configFileList
     * @param File $file
     * @param XmlEncoder $encoder
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ConfigFileList $configFileList,
        File $file,
        XmlEncoder $encoder,
        ResultFactory $resultFactory
    ) {
        $this->logger = $logger;
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
        $envVarReportDirNestingLevel = $this->environment->getEnvVarMageErrorReportDirNestingLevel();
        $reportConfigFile = $this->configFileList->getErrorReportConfig();

        try {
            if (false !== $envVarReportDirNestingLevel) {
                $this->validateEnvVarReportDirNestingLevel($envVarReportDirNestingLevel);
                $this->logger->notice(sprintf(
                    'The environment variable `%s` with the value `%s` is used to configure the directories'
                    . ' nesting level for error reporting. The environment variable has a higher priority.  Value of'
                    . ' the property `config.report.dir_nesting_level` in the file %s will be ignored.',
                    Environment::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                    $envVarReportDirNestingLevel,
                    $reportConfigFile
                ));
                return $this->resultFactory->success();
            }

            $reportConfigDirNestingLevel = $this->getReportConfigDirNestingLevel($reportConfigFile);
            if (null !== $reportConfigDirNestingLevel) {
                $this->validateReportConfigDirNestingLevel($reportConfigDirNestingLevel);
                $this->logger->notice(sprintf(
                    'The property `config.report.dir_nesting_level` defined in the file %s with value '
                    . '`%s` and  used to configure the directories nesting level for error reporting.',
                    $reportConfigFile,
                    $reportConfigDirNestingLevel
                ));
                return $this->resultFactory->success();
            }

            return $this->resultFactory->error(
                'The directories nesting level for error reporting not set.',
                'For set the directories nesting level use setting '
                . '`config.report.dir_nesting_level` in the file ' . $reportConfigFile
            );
        } catch (FileSystemException $exception) {
            return $this->resultFactory->error($exception->getMessage());
        } catch (NotEncodableValueException $exception) {
            $message = sprintf('Config of the file %s is invalid. ', $reportConfigFile);
            $message .= $exception->getMessage();
            return $this->resultFactory->error(
                $message,
                'Fix the configuration of the directories nesting level for error reporting '
                . 'in the file ' . $reportConfigFile
            );
        } catch (ValidatorException $exception) {
            return $this->resultFactory->error($exception->getMessage(), $exception->getSuggestion());
        }
    }

    /**
     * Validates a value of the env variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL
     *
     * @param string|int $envVarReportDirNestingLevel
     * @throws ValidatorException
     * @return void
     */
    private function validateEnvVarReportDirNestingLevel($envVarReportDirNestingLevel)
    {
        if ($this->validateReportDirNestingLevel($envVarReportDirNestingLevel)) {
            return;
        }
        throw new ValidatorException(
            sprintf(
                'The value `%s` of the environment variable `%s` is invalid.',
                $envVarReportDirNestingLevel,
                Environment::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL
            ),
            sprintf(
                'The value of the environment variable `%s` must be an integer between 0 and 32',
                Environment::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL
            )
        );
    }

    /**
     * Validates a value of the property `config.report.dir_nesting_level`
     * from the file <magento_root>/pub/errors/local.xml
     *
     * @param string|int $reportConfigDirNestingLevel
     * @throws ValidatorException
     * @return void
     */
    private function validateReportConfigDirNestingLevel($reportConfigDirNestingLevel)
    {
        if ($this->validateReportDirNestingLevel($reportConfigDirNestingLevel)) {
            return;
        }
        throw new ValidatorException(
            sprintf(
                'The value `%s` of the property `config.report.dir_nesting_level` '
                . 'defined in <magento_root>/pub/errors/local.xml is invalid',
                $reportConfigDirNestingLevel
            ),
            'The value of the property `config.report.dir_nesting_level` must be an integer between 0 and 32'
        );
    }

    /**
     * Validates a value of the directories nesting level
     *
     * @param string|int $value
     * @return bool
     */
    private function validateReportDirNestingLevel($value)
    {
        $valueInt = (int)$value;
        return (bool)preg_match('/^([0-9]{1,2})$/', (string)$value) && (0 <= $valueInt) && (32 >= $valueInt);
    }

    /**
     * Returns a value of the property `config.report.dir_nesting_level`
     * from the file  <magento_root>/pub/errors/local.xml
     *
     * @param string $reportConfigFile
     * @return string|null
     * @throws FileSystemException | NotEncodableValueException
     */
    private function getReportConfigDirNestingLevel($reportConfigFile)
    {
        $errorReportConfig = $this->encoder->decode(
            $this->file->fileGetContents($reportConfigFile),
            XmlEncoder::FORMAT
        ) ?: [];
        return $errorReportConfig['report']['dir_nesting_level'] ?? null;
    }
}
