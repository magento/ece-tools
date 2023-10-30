<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs a command to generate a module for eventing and enables this module in case when
 * it is enabled in configuration
 */
class EnableEventing implements StepInterface
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
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     * @param GlobalSection $globalConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellFactory $shellFactory,
        GlobalSection $globalConfig
    ) {
        $this->logger = $logger;
        $this->magentoShell = $shellFactory->createMagento();
        $this->globalConfig = $globalConfig;
    }

    /**
     * Generates and enables a module for eventing if @see StageConfigInterface::VAR_ENABLE_EVENTING set to true
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        try {
            if (!$this->globalConfig->get(StageConfigInterface::VAR_ENABLE_EVENTING)) {
                return;
            }
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->logger->notice('Generating module for eventing');
            $this->magentoShell->execute('events:generate:module');
        } catch (ShellException $e) {
            $this->logger->error(
                'Failed to generate the Magento_AdobeCommerceEvents module. ' .
                'Refer to the eventing documentation to determine if all required modules have been installed. ' .
                'Error: ' . $e->getMessage()
            );
            throw new StepException($e->getMessage(), Error::GLOBAL_EVENTING_MODULE_GENERATE_FAILED, $e);
        }

        try {
            $this->logger->notice('Enabling module for eventing');
            $this->magentoShell->execute('module:enable Magento_AdobeCommerceEvents');
        } catch (ShellException $e) {
            $this->logger->error('Failed to enable module for eventing: ' . $e->getMessage());
            throw new StepException($e->getMessage(), Error::GLOBAL_EVENTING_MODULE_ENABLEMENT_FAILED, $e);
        }
    }
}
