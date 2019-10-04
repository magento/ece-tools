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
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Psr\Log\LoggerInterface;

/**
 * Validates the value specified for the directory nesting level conifgured for error reporting
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
        $envVarValue = $this->environment->getEnvVarMageErrorReportDirNestingLevel();
        $reportConfigFile = $this->configFileList->getErrorReportConfig();

        try {
            if (false !== $envVarValue) {
                $this->logger->notice(sprintf(
                    'The `%s` environment variable with the value `%s` specifies a custom value for'
                    . ' the directory nesting level configured for error reporting. This value overrides'
                    . ' the value specified in the `config.report.dir_nesting_level` property in file %s,'
                    . ' which will be ignored.',
                    Environment::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                    $envVarValue,
                    $reportConfigFile
                ));
                return $this->resultFactory->success();
            }

            $configValue = $this->getConfigValue($reportConfigFile);
            if (null !== $configValue) {
                $this->logger->notice(sprintf(
                    'The `config.report.dir_nesting_level` property defined in the file %s with value '
                    . '`%s` and used to configure the directories nesting level for error reporting.',
                    $reportConfigFile,
                    $configValue
                ));
                return $this->resultFactory->success();
            }

            return $this->resultFactory->error(
                'The directory nesting level value for error reporting has not been configured.',
                'You can configure the setting using the `config.report.dir_nesting_level`'
                . ' in the file ' . $reportConfigFile
            );
        } catch (FileSystemException $exception) {
            return $this->resultFactory->error($exception->getMessage());
        } catch (NotEncodableValueException $exception) {
            $message = sprintf('Config of the file %s is invalid. ', $reportConfigFile);
            $message .= $exception->getMessage();
            return $this->resultFactory->error(
                $message,
                'Fix the directory nesting level configuration for error reporting in the file '
                . $reportConfigFile
            );
        }
    }

    /**
     * Returns a value of the property `config.report.dir_nesting_level`
     * from the file  <magento_root>/pub/errors/local.xml
     *
     * @param string $file
     * @return int|string|null
     * @throws FileSystemException | NotEncodableValueException
     */
    private function getConfigValue(string $file)
    {
        $errorReportConfig = $this->encoder->decode(
            $this->file->fileGetContents($file),
            XmlEncoder::FORMAT
        ) ?: [];
        return $errorReportConfig['report']['dir_nesting_level'] ?? null;
    }
}
