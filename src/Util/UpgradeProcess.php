<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for run upgrade process.
 */
class UpgradeProcess
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param FileList $fileList
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        FileList $fileList,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->fileList = $fileList;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @throws ConfigException
     * @throws UndefinedPackageException
     * @throws ShellException
     */
    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        $installUpgradeLog = $this->fileList->getInstallUpgradeLog();

        $this->logger->info('Running setup upgrade.');

        $this->shell->execute('echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog);
        $this->shell->execute(sprintf(
            '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
            'php ./bin/magento setup:upgrade --keep-generated --ansi --no-interaction ' . $verbosityLevel,
            $installUpgradeLog
        ));
    }
}
