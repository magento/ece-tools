<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Integration\Command;

use Magento\MagentoCloud\App\ContainerException;
use Magento\MagentoCloud\Command\ConfigCreate;
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
class ConfigCreateTest extends TestCase
{
    /**
     * @var ConfigCreate
     */
    private $command;

    /**
     * @var string
     */
    private $baseDir = __DIR__ . '/_files/ConfigCreate';

    /**
     * @throws ContainerException
     */
    protected function setUp(): void
    {
        $container = Container::getInstance(ECE_BP, $this->baseDir);

        $this->command = new ConfigCreate(
            $container->get(ConfigFileList::class),
            $container->get(File::class)
        );
    }

    /**
     * @param array $inputConfiguration
     * @param string $expectedFile
     * @throws \ReflectionException
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $inputConfiguration, string $expectedFile)
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(ConfigCreate::ARG_CONFIGURATION)
            ->willReturn(json_encode($inputConfiguration));

        $this->command->execute($inputMock, $outputMock);

        $this->assertEquals(
            file_get_contents($this->baseDir . '/' . $expectedFile),
            file_get_contents($this->baseDir . '/.magento.env.yaml')
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                ['stage' => ['build' => ['SCD_THREADS' => 3]]],
                '.magento.env.scd.yaml'
            ],
            [
                [
                    'stage' => [
                        'build' => [
                            BuildInterface::VAR_SCD_THREADS => 3,
                            BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL => 10,
                        ],
                        'deploy' => [
                            DeployInterface::VAR_DATABASE_CONFIGURATION => [
                                '_merge' => true,
                                'connection' => [
                                    'default' => [
                                        'host' => 'localhost',
                                        'port' => '3307',
                                        'password' => '1234',
                                        'user' => 'localuser'
                                    ]
                                ]
                            ],
                            DeployInterface::VAR_LOCK_PROVIDER => 'db',
                        ],
                    ]
                ],
                '.magento.env.dbconfiguration.yaml'
            ],
        ];
    }
}
