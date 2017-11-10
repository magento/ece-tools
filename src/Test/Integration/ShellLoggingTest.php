<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * @inheritdoc
 */
class ShellLoggingTest extends TestCase
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        Bootstrap::create()
            ->createApplication(['variables' => ['ADMIN_EMAIL' => 'admin@example.com']]);
        $this->shell = Container::getInstance()
            ->get(ShellInterface::class);
        $this->fileList = Container::getInstance()
            ->get(FileList::class);
    }

    public function testShellLogging()
    {
        $this->shell->execute('echo Magento Cloud');
        $logContent = $this->getLogContent();
        $this->assertContains('echo Magento Cloud', $logContent);
        $this->assertContains('0 => \'Magento Cloud\'', $logContent);
    }

    public function testShellLoggingWithNonZeroCode()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(127);
        $this->expectExceptionMessage('Command non-exist-command returned code 127');

        $this->shell->execute('non-exist-command');
        $logContent = $this->getLogContent();
        $this->assertContains('Command: non-exist-command ', $logContent);
        $this->assertContains('Command: non-exist-command ', $logContent);
    }

    private function getLogContent()
    {
        return file_get_contents($this->fileList->getCloudLog());
    }
}
