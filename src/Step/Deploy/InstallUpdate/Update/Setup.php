<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Util\UpgradeProcess;
use Magento\MagentoCloud\Step\StepException;

/**
 * @inheritdoc
 */
class Setup implements StepInterface
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var UpgradeProcess
     */
    private $upgradeRunner;

    /**
     * @param FlagManager $flagManager
     * @param UpgradeProcess $upgradeRunner
     */
    public function __construct(
        FlagManager $flagManager,
        UpgradeProcess $upgradeRunner
    ) {
        $this->flagManager = $flagManager;
        $this->upgradeRunner = $upgradeRunner;
    }

    /**
     * @inheritdoc
     *
     * @throws ConfigurationMismatchException
     * @throws StepException
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
        try {
            $this->upgradeRunner->execute();
        } catch (\Exception $exception) {
            //Rollback required by database
            throw new StepException($exception->getMessage(), 6, $exception);
        }
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
