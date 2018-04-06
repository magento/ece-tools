<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnDemand;
use Magento\MagentoCloud\Test\Integration\AbstractTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class ScdOnDemandTest extends AbstractTest
{
    public function testDefault()
    {
        $application = $this->bootstrap->createApplication([]);

        $commandTester = new CommandTester($application->get(ScdOnDemand::NAME));
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertContains('SCD on demand is disabled', $commandTester->getDisplay());
    }

    public function testToBeEnabled()
    {
        $application = $this->bootstrap->createApplication([]);

        $this->bootstrap->execute(sprintf(
            'cp -f %s %s',
            __DIR__ . '/_files/scd_on_demand_enabled.yaml',
            $this->bootstrap->getSandboxDir() . '/.magento.env.yaml'
        ));

        $commandTester = new CommandTester($application->get(ScdOnDemand::NAME));
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertContains('SCD on demand is enabled', $commandTester->getDisplay());
    }
}
