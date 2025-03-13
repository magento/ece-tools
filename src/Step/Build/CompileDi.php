<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;

/**
 * @inheritdoc
 */
class CompileDi implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellFactory $shellFactory,
        BuildInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->magentoShell = $shellFactory->createMagento();
        $this->stageConfig = $stageConfig;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        try {
            $this->logger->notice('Running DI compilation');
            $this->magentoShell->execute(
                'setup:di:compile',
                [
                    $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS)
                ]
            );
            $this->logger->notice('End of running DI compilation');
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_DI_COMPILATION_FAILED, $e);
        }
    }
}
