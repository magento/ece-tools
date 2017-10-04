<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ConfigFileScd;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConfigFileScdTest extends TestCase
{
    /**
     * @var ConfigFileScd
     */
    private $configFileScd;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->configFileScd = new ConfigFileScd(
            new ArrayManager(),
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
            ->method('requireFile')
            ->with('magento_root/app/etc/config.php')
            ->willReturn([
                'scopes' => [
                    'websites' => [
                        'key' => 'value'
                    ]
                ]
            ]);

        $result = $this->configFileScd->run();

        $this->assertFalse($result->hasErrors());
    }

    public function testRunScdConfigNotExists()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->with('magento_root/app/etc/config.php')
            ->willReturn([]);

        $result = $this->configFileScd->run();

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(
            ['No stores/website/locales found in config.php'],
            $result->getErrors()
        );
        $this->assertEquals(
            'To speed up deploy process please run the following commands' . PHP_EOL
            . '1. bin/magento app:config:dump' . PHP_EOL
            . '2. git add -f app/etc/config.php' . PHP_EOL
            . '3. git commit -a -m \'updating config.php\'' . PHP_EOL
            . '4. git push',
            $result->getSuggestion()
        );
    }
}
