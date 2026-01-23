<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\Command\ConfigCreate;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigCreateTest extends TestCase
{
    /**
     * @var ConfigCreate
     */
    private $command;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var InputInterface|MockObject
     */
    private $inputMock;

    /**
     * @var OutputInterface|MockObject
     */
    private $outputMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->inputMock = $this->createMock(InputInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        $this->command = new ConfigCreate(
            $this->configFileListMock,
            $this->fileMock
        );
    }

    /**
     * Test execute method.
     *
     * @param string $configuration
     * @param string $expected
     * @dataProvider executeDataProvider
     * @return void
     * @throws \Exception
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(string $configuration, string $expected): void
    {
        $this->inputMock->expects($this->once())
            ->method('getArgument')
            ->with('configuration')
            ->willReturn($configuration);
        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn('/path/to/.magento.env.yaml');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('/path/to/.magento.env.yaml', $expected);

        $this->command->execute($this->inputMock, $this->outputMock);
    }

    /**
     * Execute data provider method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                '{"stage":{"build":{"SKIP_COMPOSER_DUMP_AUTOLOAD":false}}}',
                "stage:\n  build:\n    SKIP_COMPOSER_DUMP_AUTOLOAD: false\n"
            ],
            [
                '{"stage":{"deploy":{"DATABASE_CONFIGURATION":{"password":"test", "_merge":true}}}}',
                "stage:\n  deploy:\n    DATABASE_CONFIGURATION:\n      password: test\n      _merge: true\n"
            ],
        ];
    }

    /**
     * Test execute method with wrong argument.
     *
     * @return void
     * @throws \Exception
     */
    public function testExecuteWithWrongArgument(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/Wrong JSON format.*/');

        $this->inputMock->expects($this->once())
            ->method('getArgument')
            ->with('configuration')
            ->willReturn('wrong-json');
        $this->outputMock->expects($this->never())
            ->method('writeln');

        $this->command->execute($this->inputMock, $this->outputMock);
    }
}
