<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\DumpGenerator;
use Magento\MagentoCloud\DB\DumpProcessor;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AllowMockObjectsWithoutExpectations]
class DumpProcessorTest extends TestCase
{
    /**
     * @var DumpProcessor
     */
    private $dumpProcessor;

    /**
     * @var MaintenanceModeSwitcher|MockObject
     */
    private $maintenanceModeSwitcherMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * @var BackgroundProcess|MockObject
     */
    private $backgroundProcessMock;

    /**
     * @var DumpGenerator|MockObject
     */
    private $dumpGeneratorMock;

    /**
     * @var ConnectionFactory|MockObject
     */
    private $connectionFactoryMock;

    /**
     * @var JobUnlocker|MockObject
     */
    private $jobUnlockerMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var ConnectionInterface
     */
    private $connectionDataMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->maintenanceModeSwitcherMock = $this->createMock(MaintenanceModeSwitcher::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);
        $this->backgroundProcessMock = $this->createMock(BackgroundProcess::class);
        $this->dumpGeneratorMock = $this->createMock(DumpGenerator::class);
        $this->connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);

        $this->connectionFactoryMock->method('create')
            ->willReturn($this->connectionDataMock);

        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->with('The job is terminated due to database dump');
        $this->cronSwitcherMock->expects($this->once())
            ->method('enable');
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('disable');

        $this->dumpProcessor = new DumpProcessor(
            $this->maintenanceModeSwitcherMock,
            $this->cronSwitcherMock,
            $this->backgroundProcessMock,
            $this->dumpGeneratorMock,
            $this->connectionFactoryMock,
            $this->jobUnlockerMock,
            $this->dbConfigMock
        );
    }

    /**
     * Test execute method.
     *
     * @param array $dbConfig
     * @param int $expectCount
     * @param bool $removeDefiners
     * @throws ConfigException
     * @throws FileSystemException
     * @throws GenericException
     * @throws UndefinedPackageException
     * @dataProvider executeDataProvider
     * @return void
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(array $dbConfig, int $expectCount, bool $removeDefiners): void
    {
        $series = [
            ['main', $this->connectionDataMock, $removeDefiners],
            ['quote', $this->connectionDataMock, $removeDefiners],
            ['sales', $this->connectionDataMock, $removeDefiners]
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('enable');
        $this->cronSwitcherMock->expects($this->once())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');
        $mocks = [
            'main',
            'quote',
            'sales'
        ];
        $this->dumpGeneratorMock->expects($this->exactly($expectCount))
            ->method('create')
            // withConsecutive() alternative.
            ->with(
                $this->callback(function (string $mock) use (&$mocks) {
                    return array_shift($mocks) === $mock;
                }),
                $this->connectionDataMock,
                $removeDefiners
            );

        $this->dumpProcessor->execute($removeDefiners);
    }

    /**
     * Data provider for execute method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ],
                ],
                'expectCount' => 1,
                'removeDefiners' => true
            ],
            [
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                        'checkout' => [],
                        'sales' => [],
                    ],
                ],
                'expectCount' => 3,
                'removeDefiners' => false
            ]
        ];
    }

    /**
     * Test execute method without connections.
     *
     * @return void
     */
    public function testExecuteWithoutConnections(): void
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->expectException(GenericException::class);
        $this->expectExceptionMessage('Database configuration does not exist');

        $this->maintenanceModeSwitcherMock->expects($this->never())
            ->method('enable');
        $this->cronSwitcherMock->expects($this->never())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->never())
            ->method('kill');
        $this->dumpGeneratorMock->expects($this->never())
            ->method('create');

        $this->dumpProcessor->execute(false);
    }

    /**
     * Test execute method with databases.
     *
     * @param array $databases
     * @param int $expectCount
     * @throws ConfigException
     * @throws FileSystemException
     * @throws GenericException
     * @throws UndefinedPackageException
     * @dataProvider executeWithDatabasesDataProvider
     * @return void
     */
    #[DataProvider('executeWithDatabasesDataProvider')]
    public function testExecuteWithDatabases(
        array $databases,
        int $expectCount
    ): void {
        $series = [
            ['main', $this->connectionDataMock, true],
            ['quote', $this->connectionDataMock, true],
            ['sales', $this->connectionDataMock, true]
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => [],
                    'sales' => [],
                ],
            ]);
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('enable');
        $this->cronSwitcherMock->expects($this->once())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');
        $mocks = [
            'main',
            'quote',
            'sales'
        ];
        $this->dumpGeneratorMock->expects($this->exactly($expectCount))
            ->method('create')
            // withConsecutive() alternative.
            ->with(
                $this->callback(function (string $mock) use (&$mocks) {
                    return array_shift($mocks) === $mock;
                }),
                $this->connectionDataMock,
                true
            );

        $this->dumpProcessor->execute(true, $databases);
    }

    /**
     * Data provider for executeWithDatabases method.
     *
     * @return array
     */
    public static function executeWithDatabasesDataProvider(): array
    {
        return [
            [
                'databases' => ['main'],
                'expectCount' => 1,
            ],
            [
                'databases' => ['main', 'quote'],
                'expectCount' => 2,
            ],
            [
                'databases' => ['main', 'quote', 'sales'],
                'expectCount' => 3,
            ]
        ];
    }

    /**
     * Test execute method with unavailable connection.
     *
     * @return void
     */
    public function testExecuteWithUnavailableConnection(): void
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'sales' => [],
                ],
            ]);

        $this->expectException(GenericException::class);
        $this->expectExceptionMessage(
            'Environment does not have connection `checkout` associated with database `quote`'
        );

        $this->maintenanceModeSwitcherMock->expects($this->never())
            ->method('enable');
        $this->cronSwitcherMock->expects($this->never())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->never())
            ->method('kill');
        $this->dumpGeneratorMock->expects($this->never())
            ->method('create');

        $this->dumpProcessor->execute(false, ['main', 'quote', 'sales']);
    }
}
