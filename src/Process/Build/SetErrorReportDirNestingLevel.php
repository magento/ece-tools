<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetErrorReportDirNestingLevel implements ProcessInterface
{
    const ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL = 'MAGE_ERROR_REPORT_DIR_NESTING_LEVEL';
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $env;

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
     * @param Environment $env
     * @param ConfigFileList $configFileList
     * @param BuildInterface $stageConfig
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        ConfigFileList $configFileList,
        BuildInterface $stageConfig,
        File $file
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->configFileList = $configFileList;
        $this->stageConfig = $stageConfig;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Configuring directory nesting for saving error reports');
        try {
            $envMageErrorReportDirNestingLevel = $this->env->get(self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL);
            $errorReportConfigFile = $this->configFileList->getErrorReportConfig();
            if ($envMageErrorReportDirNestingLevel) {
                $this->logger->notice(
                    sprintf(
                        '%s environment variable detected. %s: %s. '
                        .'Value of the property \'config.report.dir_nesting_level\' from \'%s\' file be ignored',
                        self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                        self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                        $envMageErrorReportDirNestingLevel,
                        $errorReportConfigFile
                    )
                );
                return;
            }
            if ($this->file->isExists($errorReportConfigFile)) {
                $this->logger->notice(
                    sprintf(
                        'Error reports configuration file \'%s exists\'. Value of the property \'%s\' be ignored',
                        $errorReportConfigFile,
                        BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
                    )
                );
                return;
            }
            $errorReportDirNestingLevel = $this->stageConfig->get(BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL);
            $this->file->filePutContents(
                $errorReportConfigFile,
                <<<XML
<?xml version="1.0"?>
<config>
    <report>
        <dir_nesting_level>{$errorReportDirNestingLevel}</dir_nesting_level>
    </report>
</config> 
XML
            );
            $this->logger->notice(
                sprintf(
                    'The file %s with property \'config.report.dir_nesting_level\' was created. ',
                    $errorReportConfigFile
                )
            );
        } catch (\Exception $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
