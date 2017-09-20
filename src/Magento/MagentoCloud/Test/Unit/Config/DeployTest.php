<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DeployTest extends TestCase
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
     * @var Deploy\Reader|Mock
     */
    private $readerMock;

    /**
     * @var Deploy\Writer|Mock
     */
    private $writerMock;

    /**
     * @var Deploy
     */
    private $deploy;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->readerMock = $this->createMock(Deploy\Reader::class);
        $this->writerMock = $this->createMock(Deploy\Writer::class);

        $this->deploy = new Deploy(
            $this->loggerMock,
            $this->connectionMock,
            $this->readerMock,
            $this->writerMock
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
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertFalse($this->invokeArgsOnMethodUsingReflection($this->deploy, "isInstalled"));
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
     * @expectedExceptionCode 5
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
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->invokeArgsOnMethodUsingReflection($this->deploy, "isInstalled");
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

    public function testIsInstalledConfigFileIsNotExistsOrEmpty()
    {
        $date = 'Wed, 13 Sep 2017 13:41:32 +0000';
        $config['install']['date'] = $date;

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);

        $dateMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'date');
        $dateMock->expects($this->once())
            ->with('r')
            ->willReturn($date);

        $this->assertTrue($this->invokeArgsOnMethodUsingReflection($this->deploy, "isInstalled"));
    }

    public function testIsInstalledConfigFileWithDate()
    {
        $date = 'Wed, 12 Sep 2017 10:40:30 +0000';
        $config = ['install' => ['date' => $date]];

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking if db exists and has tables'],
                ['Magento was installed on ' . $date]
            );
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->writerMock->expects($this->never())
            ->method('update');

        $this->assertTrue($this->invokeArgsOnMethodUsingReflection($this->deploy, "isInstalled"));
    }

    private function invokeArgsOnMethodUsingReflection(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
