<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Magento\MagentoCloud\Test\DockerIntegration\Process;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * @inheritdoc
 *
 * @php 7.2
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
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

        $assertContentPresence = function () {
            $process = new Process\Curl();
            $process->setTimeout(null)
                ->run();

            $this->assertSame(0, $process->getExitCode());
            $this->assertContains('Home page', $process->getOutput());
        };

        $assertContentPresence();

        $code = (new Process\Ece('config:dump', Config::CONTAINER_DEPLOY))
            ->run();

        $this->assertSame(0, $code);

        $to = sys_get_temp_dir() . '/app/etc/config.php';

        $read = new Process\Copy('/app/etc/config.php', $to);
        $read->mustRun();

        $config = require $to;

        $arrayManager = new ArrayManager();
        $flattenKeysConfig = implode(array_keys($arrayManager->flatten($config, '#')));

        $this->assertContains('#modules', $flattenKeysConfig);
        $this->assertContains('#scopes', $flattenKeysConfig);
        $this->assertContains('#system/default/general/locale/code', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/static/sign', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/front_end_development_workflow', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/template', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/js', $flattenKeysConfig);
        $this->assertContains('#system/default/dev/css', $flattenKeysConfig);
        $this->assertContains('#system/stores', $flattenKeysConfig);
        $this->assertContains('#system/websites', $flattenKeysConfig);
        $this->assertContains('#admin_user/locale/code', $flattenKeysConfig);

        $assertContentPresence();
    }
}
