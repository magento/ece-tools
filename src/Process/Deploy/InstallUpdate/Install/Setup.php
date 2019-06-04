<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Setup implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var InstallCommandFactory
     */
    private $commandFactory;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param FileList $fileList
     * @param InstallCommandFactory $commandFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        FileList $fileList,
        InstallCommandFactory $commandFactory
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->fileList = $fileList;
        $this->commandFactory = $commandFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Installing Magento.');

        try {
            $installUpgradeLog = $this->fileList->getInstallUpgradeLog();

            $this->shell->execute('echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog);
            $this->shell->execute(sprintf(
                '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
                escapeshellcmd($this->commandFactory->create()),
                $installUpgradeLog
            ));
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
