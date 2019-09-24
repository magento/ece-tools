<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

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
        $this->logger->info('Configuring directory nesting for saving error reports');
        try {
            $errorReportConfigFile = $this->configFileList->getErrorReportConfig();
            if ($this->file->isExists($errorReportConfigFile)) {
                $this->logger->notice(
                    sprintf(
                        'Error reports configuration file \'%s exists\'. Value of the property \'%s\' of .magento.env.yaml be ignored',
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
                    'The file %s with property \'config.report.dir_nesting_level\': %s was created.',
                    $errorReportConfigFile,
                    $errorReportDirNestingLevel
                )
            );
        } catch (\Exception $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
