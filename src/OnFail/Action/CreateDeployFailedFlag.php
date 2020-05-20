<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\OnFail\Action;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;

/**
 * Creates deploy_is_failed flag if deploy is failed.
 */
class CreateDeployFailedFlag implements ActionInterface
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function execute(): void
    {
        $this->flagManager->set(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
    }
}
