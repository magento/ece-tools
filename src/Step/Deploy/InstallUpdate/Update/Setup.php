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
    private $upgradeProcess;

    /**
     * @param FlagManager $flagManager
     * @param UpgradeProcess $upgradeProcess
     */
    public function __construct(
        FlagManager $flagManager,
        UpgradeProcess $upgradeProcess
    ) {
        $this->flagManager = $flagManager;
        $this->upgradeProcess = $upgradeProcess;
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
            $this->upgradeProcess->execute();
        } catch (\Exception $exception) {
            //Rollback required by database
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
