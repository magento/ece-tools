<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Kills all running Magento cron and consumers processes
 */
class BackgroundProcessKill implements ProcessInterface
{
    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;

    /**
     * @param BackgroundProcess $backgroundProcess
     */
    public function __construct(BackgroundProcess $backgroundProcess)
    {
        $this->backgroundProcess = $backgroundProcess;
    }

    /**
     * Kills all running Magento cron jobs and consumers processes.
     *
     * @return void
     */
    public function execute()
    {
        $this->backgroundProcess->kill();
    }
}
