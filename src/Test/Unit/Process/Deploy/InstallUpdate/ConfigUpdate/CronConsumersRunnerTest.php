<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\CronConsumersRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class CronConsumersRunnerTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var CronConsumersRunner
     */
    private $cronConsumersRunner;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->cronConsumersRunner = new CronConsumersRunner(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    /**
     * @param array $config
     * @param array $configFromVariable
     * @param array $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $config, array $configFromVariable, array $expectedResult)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php cron consumers runner configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->environmentMock->expects($this->once())
            ->method('getCronConsumersRunner')
            ->willReturn($configFromVariable);
        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with($expectedResult);

        $this->cronConsumersRunner->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'config' => [],
                'configFromVariable' => [],
                'expectedResult' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                    ],
                ],
                'configFromVariable' => [],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                    ],
                ],
                'configFromVariable' => ['cron_run' => 'false'],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                    ],
                ],
                'configFromVariable' => ['cron_run' => 'true'],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                    ],
                ],
                'configFromVariable' => [
                    'cron_run' => 'true',
                    'max_messages' => 200,
                    'consumers' => ['test2', 'test3']
                ],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 200,
                        'consumers' => ['test2', 'test3'],
                    ],
                ],
            ],
        ];
    }
}
