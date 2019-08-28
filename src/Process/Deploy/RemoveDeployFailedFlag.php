<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Removes failed deploy flag.
 */
class RemoveDeployFailedFlag implements ProcessInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->manager->delete(Manager::FLAG_DEPLOY_HOOK_IS_FAILED);
    }
}
