<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\NewRelic\Client as NewRelicClient;
use Magento\MagentoCloud\Step\StepInterface;

/**
 * Register deployment in the new Relic
 */
class NewRelicRegisterDeployment implements StepInterface
{
    /**
     * @var NewRelicClient
     */
    private $newRelicClient;

    /**
     *
     * @param NewRelicClient $newRelicClient
     */
    public function __construct(NewRelicClient $newRelicClient)
    {
        $this->newRelicClient = $newRelicClient;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->newRelicClient->registerDeployment();
    }
}
