<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnBuild;
use Magento\MagentoCloud\Test\Integration\AbstractTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class ScdOnBuildTest extends AbstractTest
{
    public function testDefault()
    {
        $application = $this->bootstrap->createApplication([]);

        $commandTester = new CommandTester($application->get(ScdOnBuild::NAME));
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertContains(' - No stores/website/locales found in', $commandTester->getDisplay());
        $this->assertContains('SCD on build is disabled', $commandTester->getDisplay());
    }

    public function testToBeEnabled()
    {
        $application = $this->bootstrap->createApplication([]);

        $this->bootstrap->execute(sprintf(
            'cp -f %s %s',
            __DIR__ . '/_files/config_scd_in_build.php',
            $this->bootstrap->getSandboxDir() . '/app/etc/config.php'
        ));

        $commandTester = new CommandTester($application->get(ScdOnBuild::NAME));
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertContains('SCD on build is enabled', $commandTester->getDisplay());
    }
}
