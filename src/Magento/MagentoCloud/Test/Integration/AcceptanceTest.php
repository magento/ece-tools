<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Config\Environment;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class AcceptanceTest extends TestCase
{
    /**
     * @param array $environment
     * @dataProvider dataProvider
     */
    public function test(array $environment)
    {
        $application = $this->createApplication($environment);

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
            'disabled static content symlinks ' => [
                'environment' => [
                    'variables' => [
                        'STATIC_CONTENT_SYMLINK' => Environment::VAL_DISABLED,
                    ],
                ],
            ],
        ];
    }
}
