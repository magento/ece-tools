<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Shell\UtilityManager;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Setup implements StepInterface
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
     * @var UtilityManager
     */
    private $utilityManager;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param FileList $fileList
     * @param InstallCommandFactory $commandFactory
     * @param UtilityManager $utilityManager
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        FileList $fileList,
        InstallCommandFactory $commandFactory,
        UtilityManager $utilityManager
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->fileList = $fileList;
        $this->commandFactory = $commandFactory;
        $this->utilityManager = $utilityManager;
    }

    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $this->logger->info('Installing Magento.');

        try {
            $installUpgradeLog = $this->fileList->getInstallUpgradeLog();

            $this->shell->execute('echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog);
            $this->shell->execute(sprintf(
                '%s -c "set -o pipefail; %s | tee -a %s"',
                $this->utilityManager->get(UtilityManager::UTILITY_SHELL),
                escapeshellcmd($this->commandFactory->create()),
                $installUpgradeLog
            ));
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_INSTALL_COMMAND_FAILED, $e);
        } catch (UtilityException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_UTILITY_NOT_FOUND, $e);
        } catch (GenericException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
