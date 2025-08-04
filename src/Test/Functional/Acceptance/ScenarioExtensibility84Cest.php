<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Tests extensibility base deployment scenarios
 *
 * @group php84
 */
class ScenarioExtensibility84Cest extends ScenarioExtensibilityCest
{
    /**
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.8';
}
