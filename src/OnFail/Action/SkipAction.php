<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\OnFail\Action;

use Psr\Log\LoggerInterface;

/**
 * Class for skipped actions.
 * Logs the information about skipped tests.
 */
class SkipAction implements ActionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $actionName;

    /**
     * @param LoggerInterface $logger
     * @param string $actionName
     */
    public function __construct(LoggerInterface $logger, string $actionName)
    {
        $this->logger = $logger;
        $this->actionName = $actionName;
    }

    /**
     * Logs the information about action skipping
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info(sprintf('Step "%s" was skipped', $this->actionName));
    }
}
