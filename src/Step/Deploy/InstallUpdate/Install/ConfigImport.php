<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;

/**
 * Imports configurations after changes env.php
 *
 * {@inheritdoc}
 */
class ConfigImport implements StepInterface
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ShellFactory $shellFactory
     * @param LoggerInterface $logger
     * @param MagentoVersion $version
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ShellFactory $shellFactory,
        LoggerInterface $logger,
        MagentoVersion $version,
        DeployInterface $stageConfig
    ) {
        $this->magentoShell = $shellFactory->createMagento();
        $this->logger = $logger;
        $this->magentoVersion = $version;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
                return;
            }

            $this->logger->info('Run app:config:import command');
            $this->magentoShell->execute(
                'app:config:import',
                [$this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)]
            );
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_CONFIG_IMPORT_COMMAND_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
