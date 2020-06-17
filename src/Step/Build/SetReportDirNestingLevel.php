<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates the error handling configuration file `<magento_root>/pub/errors/local.xml`
 * that specifies the directory nesting level configuration for error reporting
 */
class SetReportDirNestingLevel implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param ConfigFileList $configFileList
     * @param BuildInterface $stageConfig
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigFileList $configFileList,
        BuildInterface $stageConfig,
        File $file
    ) {
        $this->logger = $logger;
        $this->configFileList = $configFileList;
        $this->stageConfig = $stageConfig;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->logger->info('Configuring directory nesting level for saving error reports');
            $configFile = $this->configFileList->getErrorReportConfig();
            if ($this->file->isExists($configFile)) {
                $this->logger->notice(
                    sprintf(
                        'The error reports configuration file `%s` exists.'
                        .' Value of the property `%s` of .magento.env.yaml will be ignored',
                        $configFile,
                        BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
                    )
                );
                return;
            }
            $envVarValue = $this->stageConfig->get(BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL);
            $this->file->filePutContents(
                $configFile,
                <<<XML
<?xml version="1.0"?>
<config>
    <report>
        <dir_nesting_level>{$envVarValue}</dir_nesting_level>
    </report>
</config> 
XML
            );
            $this->logger->notice(
                sprintf(
                    'The file %s with the `config.report.dir_nesting_level` property: `%s` was created.',
                    $configFile,
                    $envVarValue
                )
            );
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_FILE_LOCAL_XML_IS_NOT_WRITABLE, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
