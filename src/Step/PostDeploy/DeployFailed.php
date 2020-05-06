<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;

/**
 * @inheritDoc
 */
class DeployFailed implements StepInterface
{
    /**
     * @var Manager
     */
    private $flagManager;

    /**
     * @param Manager $flagManager
     */
    public function __construct(Manager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if ($this->flagManager->exists(Manager::FLAG_DEPLOY_HOOK_IS_FAILED)) {
            throw new StepException('Post-deploy is skipped because deploy was failed.', Error::PD_DEPLOY_IS_FAILED);
        }
    }
}
