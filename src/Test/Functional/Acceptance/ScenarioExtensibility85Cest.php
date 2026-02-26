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
 * @group php85
 */
class ScenarioExtensibility85Cest extends ScenarioExtensibilityCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.9-alpha-opensearch3.0';
}
