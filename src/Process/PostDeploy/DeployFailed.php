<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * @inheritDoc
 */
class DeployFailed implements ProcessInterface
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param FlagManager $flagManager
     */
    public function __construct(FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if ($this->flagManager->exists(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED)) {
            throw new ProcessException('Post-deploy is skipped because deploy was failed.');
        }
    }
}
