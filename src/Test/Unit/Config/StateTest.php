<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\State;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class StateTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var State
     */
    private $state;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->state = new State(
            $this->loggerMock,
            $this->connectionMock,
            $this->readerMock,
            $this->writerMock,
            $this->environmentMock
        );
    }

    /**
     * @param mixed $tables
     * @throws GenericException
     *
     * @dataProvider tablesCountDataProvider
     */
    public function testIsInstalledTablesCount($tables): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn($tables);
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertFalse($this->state->isInstalled());
    }

    /**
     * @return array
     */
    public function tablesCountDataProvider(): array
    {
        return [[['']], [['table1']]];
    }

    /**
     * @param array $tables
     * @throws GenericException
     *
     * @dataProvider tablesWithExceptionDataProvider
     */
    public function testIsInstalledTablesWithException($tables): void
    {
        $this->expectException(GenericException::class);
        $this->expectExceptionMessage('Missing either core_config_data or setup_module table');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn($tables);
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->state->isInstalled();
    }

    /**
     * @return array
     */
    public function tablesWithExceptionDataProvider(): array
    {
        return [
            [['core_config_data', 'some_table']],
            [['setup_module', 'some_table']],
            [['some_table', 'some_table2']],
        ];
    }

    /**
     * @throws GenericException
     */
    public function testInstalledDbAndEmptyFile(): void
    {
        $this->expectException(GenericException::class);
        $this->expectExceptionMessage('Missing crypt key for upgrading Magento');
        $this->expectExceptionCode(Error::DEPLOY_CRYPT_KEY_IS_ABSENT);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->mockForTablesExist();
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('');
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->state->isInstalled();
    }

    /**
     * @throws GenericException
     */
    public function testInstalledDbAndFileWithoutDate(): void
    {
        $date = 'Wed, 13 Sep 2017 13:41:32 +0000';
        $config['install']['date'] = $date;

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->mockForTablesExist(['crypt' => ['key' => 'crypt_key_value']]);
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);

        $dateMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'date');
        $dateMock->expects($this->once())
            ->with('r')
            ->willReturn($date);

        $this->assertTrue($this->state->isInstalled());
    }

    /**
     * @throws GenericException
     */
    public function testInstalledWithCryptKeyOnlyInEnvironmentVar(): void
    {
        $date = 'Wed, 12 Sep 2017 10:40:30 +0000';
        $config = ['install' => ['date' => $date]];

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking if db exists and has tables'],
                ['Magento was installed on ' . $date]
            );
        $this->mockForTablesExist($config);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('crypt_key_value');
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertTrue($this->state->isInstalled());
    }

    /**
     * @throws GenericException
     */
    public function testIsInstalledWithFullData(): void
    {
        $date = 'Wed, 12 Sep 2017 10:40:30 +0000';
        $config = [
            'install' => ['date' => $date],
            'crypt' => ['key' => 'crypt_key_value']
        ];

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking if db exists and has tables'],
                ['Magento was installed on ' . $date]
            );
        $this->mockForTablesExist($config);
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertTrue($this->state->isInstalled());
    }

    private function mockForTablesExist($config = [])
    {
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->connectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->withConsecutive(['core_config_data'], ['setup_module'])
            ->willReturnOnConsecutiveCalls('core_config_data', 'setup_module');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
    }
}
