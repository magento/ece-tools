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
 * @group php72
 */
class UpgradeTest extends AbstractTest
{
    /**
     * @param string $updateFrom
     * @param string $updateTo
     * @dataProvider testDataProvider
     */
    public function test(string $updateFrom, string $updateTo)
    {
        $assert = function () {
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
        };

        (new Process\GitClone($updateFrom))
            ->setTimeout(null)
            ->mustRun();
        (new Process\ComposerInstall())
            ->setTimeout(null)
            ->mustRun();

        $assert();

        $magentoRoot = (new Config())->get('system.magento_dir');
        $pathsToCleanup = implode(
            ' ',
            [
                $magentoRoot . '/vendor/*',
                $magentoRoot . '/app/etc/*',
                $magentoRoot . '/setup/*'
            ]
        );

        (new Process\Bash('rm -rf ' . $pathsToCleanup, Config::DEFAULT_CONTAINER))
            ->setTimeout(null)
            ->run();

        (new Process\ComposerRequire($updateTo))
            ->setTimeout(null)
            ->mustRun();

        $assert();
    }

    /**
     * @return array
     */
    public function testDataProvider(): array
    {
        return [
            ['2.3.0', '2.3.*']
        ];
    }
}
