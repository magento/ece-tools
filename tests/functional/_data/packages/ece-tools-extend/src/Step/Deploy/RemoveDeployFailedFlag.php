<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\EceToolExtend\Step\Deploy;

use Magento\MagentoCloud\Step\Deploy\RemoveDeployFailedFlag as RemoveDeployFailedFlagOrigin;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Extended step for removing failed deploy flag.
 */
class RemoveDeployFailedFlag implements StepInterface
{
    /**
     * @var RemoveDeployFailedFlagOrigin
     */
    private $removeDeployFailedFlagOrigin;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RemoveDeployFailedFlagOrigin $removeDeployFailedFlagOrigin
     * @param LoggerInterface $logger
     */
    public function __construct(RemoveDeployFailedFlagOrigin $removeDeployFailedFlagOrigin, LoggerInterface $logger)
    {
        $this->removeDeployFailedFlagOrigin = $removeDeployFailedFlagOrigin;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->logger->info('Start of EXTENDED RemoveDeployFailedFlag step');

        $this->removeDeployFailedFlagOrigin->execute();

        $this->logger->info('Finish of EXTENDED RemoveDeployFailedFlag step');
    }
}
