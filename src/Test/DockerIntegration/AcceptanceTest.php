<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Magento\MagentoCloud\Test\DockerIntegration\Process;

/**
 * @inheritdoc
 *
 * @php 7.2
 */
class AcceptanceTest extends AbstractTest
{
    public function test()
    {
        (new Process\GitClone('master'))
            ->setTimeout(null)
            ->mustRun();
        (new Process\ComposerInstall())
            ->setTimeout(null)
            ->mustRun();

        $code = (new Process\Ece('build', Config::DEFAULT_CONTAINER))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('deploy', Config::CONTAINER_DEPLOY))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('post-deploy', Config::CONTAINER_DEPLOY))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $process = new Process\Curl();
        $process->setTimeout(null)
            ->run();

        $this->assertSame(0, $process->getExitCode());
        $this->assertContains('Home page', $process->getOutput());
    }
}
