<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Integration\Command;

use Magento\MagentoCloud\App\ContainerException;
use Magento\MagentoCloud\Command\ConfigCreate;
use Magento\MagentoCloud\Command\ConfigUpdate;
use Magento\MagentoCloud\Config\Environment\ReaderInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Test\Integration\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class ConfigUpdateTest extends TestCase
{
    /**
     * @var string
     */
    private $baseDir = __DIR__ . '/_files/ConfigUpdate';

    /**
     * @param array $inputConfiguration
     * @param string $baseDir
     * @throws ContainerException
     * @throws \ReflectionException
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $inputConfiguration, string $baseDir)
    {
        $tmpMagentoEnvYaml = file_get_contents($baseDir . '/.magento.env.yaml');

        $container = new Container(ECE_BP, $baseDir);

        $command = new ConfigUpdate(
            $container->get(ConfigFileList::class),
            $container->get(File::class),
            $container->get(ReaderInterface::class)
        );

        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(ConfigUpdate::ARG_CONFIGURATION)
            ->willReturn(json_encode($inputConfiguration));

        $command->execute($inputMock, $outputMock);

        $this->assertEquals(
            file_get_contents($baseDir . '/.magento.env_exp.yaml'),
            file_get_contents($baseDir . '/.magento.env.yaml')
        );

        file_put_contents($baseDir . '/.magento.env.yaml', $tmpMagentoEnvYaml);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [
                    'stage' => [
                        'build' => [
                            BuildInterface::VAR_SCD_THREADS => 6,
                            BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL => 12,
                        ],
                    ],
                ],
                $this->baseDir . '/scdupdate'
            ],
            [
                [
                    'stage' => [
                        'deploy' => [
                            DeployInterface::VAR_DATABASE_CONFIGURATION => [
                                '_merge' => true,
                                'connection' => [
                                    'default' => [
                                        'host' => '127.0.0.1',
                                        'port' => '3306',
                                        'password' => 'newpassword',
                                        'user' => 'newuser'
                                    ]
                                ]
                            ],
                            DeployInterface::VAR_LOCK_PROVIDER => 'redis',
                        ],
                    ]
                ],
                $this->baseDir . '/dbconfiguration'
            ],
        ];
    }
}
