<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class AcceptanceTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        shell_exec(sprintf(
            "cp -f %s %s",
            Bootstrap::create()->getConfigFile('config.php'),
            Bootstrap::create()->getSandboxDir() . '/app/etc/config.php'
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $sandboxDir = Bootstrap::create()->getSandboxDir();

        shell_exec(sprintf(
            "cd %s && php bin/magento setup:uninstall -n",
            $sandboxDir
        ));
        shell_exec(sprintf(
            "cd %s && rm -rf init",
            $sandboxDir
        ));
    }

    /**
     * @param array $environment
     * @dataProvider dataProvider
     */
    public function test(array $environment)
    {
        $application = Bootstrap::create()->createApplication($environment);

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            'default configuration' => [
                'environment' => [],
            ],
            'disabled static content symlinks 3 jobs' => [
                'environment' => [
                    'variables' => [
                        'STATIC_CONTENT_SYMLINK' => Environment::VAL_DISABLED,
                        'STATIC_CONTENT_THREADS' => 3,
                    ],
                ],
            ],
        ];
    }
}
