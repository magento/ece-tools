<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DeployTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var Deploy
     */
    private $deploy;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->markTestSkipped();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->deploy = new Deploy(
            $this->loggerMock,
            $this->connectionMock,
            $this->fileMock,
            $this->directoryListMock
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
        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->assertFalse($this->deploy->isInstalled());
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
        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->deploy->isInstalled();
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

    public function testIsInstalledConfigFileIsNotExists()
    {
        $date = 'Wed, 13 Sep 2017 13:41:32 +0000';
        $config['install']['date'] = $date;
        $pathRoot = '/magento';
        $configPath = $pathRoot . '/app/etc/env.php';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getMagentoRoot')
            ->willReturn($pathRoot);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configPath)
            ->willReturn(false);

        $dateMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'date');
        $dateMock->expects($this->once())
            ->with('r')
            ->willReturn($date);

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($configPath, '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';');

        $this->assertTrue($this->deploy->isInstalled());
    }

    public function testIsInstalledConfigFileWithDate()
    {
        $date = 'Wed, 12 Sep 2017 10:40:30 +0000';
        $pathRoot = __DIR__ . '/_file/Deploy/with_date';
        $configPath = $pathRoot . '/app/etc/env.php';

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking if db exists and has tables'],
                ['Magento was installed on ' . $date]
            );
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($pathRoot);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configPath)
            ->willReturn(true);

        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->assertTrue($this->deploy->isInstalled());
    }

    public function testIsInstalledConfigFileWithoutDate()
    {
        $date = 'Wed, 12 Sep 2017 11:45:35 +0000';
        $config['install']['date'] = $date;
        $pathRoot = __DIR__ . '/_file/Deploy/without_date';
        $configPath = $pathRoot . '/app/etc/env.php';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking if db exists and has tables');
        $this->connectionMock->expects($this->once())
            ->method('listTables')
            ->willReturn(['core_config_data', 'setup_module']);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($pathRoot);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($configPath)
            ->willReturn(true);

        $dateMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'date');
        $dateMock->expects($this->once())
            ->with('r')
            ->willReturn($date);

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($configPath, '<?php' . "\n" . 'return ' . var_export($config, true) . ';');

        $this->assertTrue($this->deploy->isInstalled());
    }
}
