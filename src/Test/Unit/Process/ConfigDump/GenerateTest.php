<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ConfigDump\Generate;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class GenerateTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var Generate
     */
    private $process;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var SharedConfig|MockObject
     */
    private $sharedConfigMock;

    /**
     * @var string
     */
    private $timeStamp = '2018-01-19T18:33:42+00:00';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->sharedConfigMock = $this->createMock(SharedConfig::class);

        $dateMock = $this->getFunctionMock('Magento\MagentoCloud\Process\ConfigDump', 'date');
        $dateMock->expects($this->any())
            ->willReturn($this->timeStamp);

        $this->process = new Generate(
            $this->connectionMock,
            $this->fileMock,
            new ArrayManager(),
            $this->magentoVersionMock,
            $this->sharedConfigMock
        );
    }

    /**
     * @param bool $versionGreaterTwoDotTwo
     * @param string $expectedResultFilePath
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $versionGreaterTwoDotTwo, string $expectedConfigFilePath)
    {
        $expectedConfig = require $expectedConfigFilePath;
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn($versionGreaterTwoDotTwo);
        $this->sharedConfigMock->method('resolve')
            ->willReturn(__DIR__ . '/_files/app/etc/config.php');
        $this->connectionMock->method('select')
            ->with('SELECT DISTINCT `interface_locale` FROM `admin_user`')
            ->willReturn([
                ['interface_locale' => 'fr_FR'],
                ['interface_locale' => 'ua_UA'],
            ]);
        $this->fileMock->method('filePutContents')
            ->with(
                __DIR__ . '/_files/app/etc/config.php',
                '<?php' . "\n" . 'return ' . var_export($expectedConfig, true) . ";\n"
            );

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'magento version greater 2.2' => [
                true,
                __DIR__ . '/_files/app/etc/generated_config.php'
            ],
            'magento version lower 2.2' => [
                false,
                __DIR__ . '/_files/app/etc/generated_config_2.1.php'
            ],
        ];
    }
}
