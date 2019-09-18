<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * @inheritDoc
 */
class DeployFailed implements ProcessInterface
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
            throw new ProcessException('Post-deploy is skipped because deploy was failed.');
        }
    }
}
