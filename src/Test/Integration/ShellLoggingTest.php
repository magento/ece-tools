<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * @inheritdoc
 */
class ShellLoggingTest extends AbstractTest
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
        parent::setUp();
        $magentoEnv = ['stage' => ['global' => ['MIN_LOGGING_LEVEL' => 'debug']]];
        file_put_contents(Bootstrap::getInstance()->getSandboxDir() . '/.magento.env.yaml', json_encode($magentoEnv));

        $application = $this->bootstrap->createApplication([
            'variables' => [
                'ADMIN_EMAIL' => 'admin@example.com',
            ],
        ]);
        $this->shell = $application->getContainer()
            ->get(ShellInterface::class);
        $this->fileList = $application->getContainer()
            ->get(FileList::class);
    }

    public function testShellLogging()
    {
        $this->shell->execute('echo Magento Cloud');
        $logContent = $this->getLogContent();
        $this->assertContains('INFO: echo Magento Cloud', $logContent);
        $this->assertContains("DEBUG: \n  Magento Cloud", $logContent);
    }

    public function testShellLoggingWithNonZeroCode()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(127);
        $this->expectExceptionMessage('Command non-exist-command returned code 127');

        $this->shell->execute('non-exist-command');
        $logContent = $this->getLogContent();
        $this->assertContains('Command: non-exist-command ', $logContent);
        $this->assertRegExp('/CRITICAL:\n.*non-exist-command: command not found/', $logContent);
    }

    private function getLogContent()
    {
        return file_get_contents($this->fileList->getCloudLog());
    }
}
