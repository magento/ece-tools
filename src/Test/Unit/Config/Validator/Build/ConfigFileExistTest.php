<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ConfigFileExist;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConfigFileTest extends TestCase
{
    /**
     * @var ConfigFileExist
     */
    private $configFile;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);


        $this->configFile = new ConfigFileExist(
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testRun()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);

        $result = $this->configFile->run();

        $this->assertFalse($result->hasErrors());
    }

    public function testRunFileNotExists()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);

        $result = $this->configFile->run();

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(
            ['File app/etc/config.php not exists'],
            $result->getErrors()
        );
        $this->assertEquals(
            'Please run the following commands' . PHP_EOL
            . '1. bin/magento module:enable --all' . PHP_EOL
            . '2. git add -f app/etc/config.php' . PHP_EOL
            . '3. git commit -a -m \'adding config.php\'' . PHP_EOL
            . '4. git push',
            $result->getSuggestion()
        );
    }
}
