<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\UtilityException;
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
     * @throws StepException
     */
    public function execute()
    {
        try {
            $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
            $this->upgradeProcess->execute();
            $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_UPGRADE_COMMAND_FAILED, $e);
        } catch (UtilityException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_UTILITY_NOT_FOUND, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
