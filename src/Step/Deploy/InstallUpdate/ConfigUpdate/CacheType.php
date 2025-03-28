<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CacheType implements StepInterface
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     * @param ShellFactory $shellFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        ShellFactory $shellFactory,
        LoggerInterface $logger
    ) {
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->magentoShell = $shellFactory->createMagento();
        $this->logger = $logger;
    }

    /**
     * Enable all cache types if env.php was absence
     *
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if ($this->flagManager->exists(FlagManager::FLAG_ENV_FILE_ABSENCE)) {
                $this->logger->info('Run cache:enable to restore all cache types');
                $this->magentoShell->execute(
                    'cache:enable',
                    [$this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)]
                );
            }
        } catch (ShellException $exception) {
            throw new StepException($exception->getMessage(), Error::DEPLOY_CACHE_ENABLE_FAILED, $exception);
        } catch (GenericException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
