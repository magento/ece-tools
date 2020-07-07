<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
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
 * Validates the value specified for the directory nesting level conifgured for error reporting
 */
class ReportDirNestingLevel implements ValidatorInterface
{
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
    ) {
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
            $configValue = $this->getConfigValue($reportConfigFile);
            if (null !== $configValue || false !== $envVarValue) {
                return $this->resultFactory->success();
            }

            return $this->resultFactory->error(
                'The directory nesting level value for error reporting has not been configured.',
                'You can configure the setting using the `config.report.dir_nesting_level` variable'
                . ' in the file ' . $reportConfigFile,
                Error::WARN_DIR_NESTING_LEVEL_NOT_CONFIGURED
            );
        } catch (FileSystemException $exception) {
            return $this->resultFactory->error($exception->getMessage());
        } catch (NotEncodableValueException $exception) {
            $message = sprintf(
                'Invalid configuration in the %s file. %s',
                $reportConfigFile,
                $exception->getMessage()
            );

            return $this->resultFactory->error(
                $message,
                'Fix the directory nesting level configuration for error reporting in the file '
                . $reportConfigFile,
                Error::WARN_NOT_CORRECT_LOCAL_XML_FILE
            );
        }
    }

    /**
     * Returns a value of the property `config.report.dir_nesting_level`
     * from the file  <magento_root>/pub/errors/local.xml
     *
     * @param string $file
     * @return int|string|null
     * @throws FileSystemException
     * @throws NotEncodableValueException
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
