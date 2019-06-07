<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\State;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class StateTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var State
     */
    private $state;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->state = new State(
            $this->loggerMock,
            $this->connectionMock
        );
    }

    /**
     * @param mixed $tables
     * @dataProvider tablesCountDataProvider
     */
    public function testIsInstalledTablesCount($tables)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn($tables);

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
     * @expectedException \Exception
     * @dataProvider tablesWithExceptionDataProvider
     */
    public function testIsInstalledTablesWithException($tables)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn($tables);

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
}
