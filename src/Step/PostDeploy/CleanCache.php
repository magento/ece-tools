<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements StepInterface
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var StageConfigInterface
     */
    private $stageConfig;

    /**
     * @var integer
     */
    private $errorCode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellFactory $shellFactory
     * @param StageConfigInterface $stageConfig
     * @param int $errorCode
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShellFactory $shellFactory,
        StageConfigInterface $stageConfig,
        int $errorCode,
        LoggerInterface $logger
    ) {
        $this->magentoShell = $shellFactory->createMagento();
        $this->stageConfig = $stageConfig;
        $this->errorCode = $errorCode;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->logger->info('Flushing cache.');
            $this->magentoShell->execute(
                'cache:flush',
                [$this->stageConfig->get(StageConfigInterface::VAR_VERBOSE_COMMANDS)]
            );
            $this->logger->info('Cache flushed successfully.');
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), $this->errorCode, $e);
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
