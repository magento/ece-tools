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
 * Runs a command to generate a module for webhooks and enables this module in case when
 * it is enabled in configuration
 */
class EnableWebhooks implements StepInterface
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
     * Generates and enables a module for Commerce webhooks
     * if @see StageConfigInterface::VAR_ENABLE_WEBHOOKS set to true
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        try {
            if (!$this->globalConfig->get(StageConfigInterface::VAR_ENABLE_WEBHOOKS)) {
                return;
            }
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->logger->notice('Generating module for Commerce webhooks');
            $this->magentoShell->execute('webhooks:generate:module');
        } catch (ShellException $e) {
            $this->logger->error(
                'Failed to generate the AdobeCommerceWebhookPlugins module. ' .
                'Refer to the Commerce webhooks documentation to determine if all ' .
                'required modules have been installed. ' .
                'Error: ' . $e->getMessage()
            );
            throw new StepException($e->getMessage(), Error::GLOBAL_WEBHOOKS_MODULE_GENERATE_FAILED, $e);
        }

        try {
            $this->logger->notice('Enabling module for Commerce webhooks');
            $this->magentoShell->execute('module:enable Magento_AdobeCommerceWebhookPlugins');
        } catch (ShellException $e) {
            $this->logger->error('Failed to enable module for Commerce webhooks: ' . $e->getMessage());
            throw new StepException($e->getMessage(), Error::GLOBAL_WEBHOOKS_MODULE_ENABLEMENT_FAILED, $e);
        }
    }
}
