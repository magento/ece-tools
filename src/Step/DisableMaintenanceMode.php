<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;

/**
 * Disables maintenance mode.
 */
class DisableMaintenanceMode implements StepInterface
{
    /**
     * @var MaintenanceModeSwitcher
     */
    private $switcher;

    /**
     * @param MaintenanceModeSwitcher $switcher
     */
    public function __construct(MaintenanceModeSwitcher $switcher)
    {
        $this->switcher = $switcher;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $this->switcher->disable();
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_MAINTENANCE_MODE_DISABLING_FAILED, $e);
        }
    }
}
