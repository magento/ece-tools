<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CheckEvnVariableMageErrorReportDirNestingLevel implements ProcessInterface
{
    const ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL = 'MAGE_ERROR_REPORT_DIR_NESTING_LEVEL';

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
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ConfigFileList $configFileList
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ConfigFileList $configFileList
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->configFileList = $configFileList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $envMageErrorReportDirNestingLevel = $this->environment->getEnv(self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL);
            if ($envMageErrorReportDirNestingLevel) {
                $this->logger->notice(
                    sprintf(
                        '%s environment variable detected. %s: %s. '
                        .'Value of the property \'config.report.dir_nesting_level\' from \'%s\' file be ignored',
                        self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                        self::ENV_MAGE_ERROR_REPORT_DIR_NESTING_LEVEL,
                        $envMageErrorReportDirNestingLevel,
                        $this->configFileList->getErrorReportConfig()
                    )
                );
                return;
            }
        } catch (\Exception $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
