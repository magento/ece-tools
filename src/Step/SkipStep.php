<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step;

use Psr\Log\LoggerInterface;

/**
 * Class for skipped steps.
 * Logs the information about skipped steps.
 */
class SkipStep implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $stepName;

    /**
     * @param LoggerInterface $logger
     * @param string $stepName
     */
    public function __construct(LoggerInterface $logger, string $stepName)
    {
        $this->logger = $logger;
        $this->stepName = $stepName;
    }

    /**
     * Logs the information about step skipping.
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        $this->logger->info(sprintf('Step "%s" was skipped', $this->stepName));
    }
}
