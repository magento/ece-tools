<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\PasswordResetEmail;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class PasswordResetEmailTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var Mock
     */
    private $mailFunctionMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var PasswordResetEmail
     */
    private $passwordResetEmail;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->mailFunctionMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install',
            'mail'
        );

        $this->passwordResetEmail = new PasswordResetEmail(
            $this->loggerMock,
            $this->environmentMock,
            $this->urlManagerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithPasswordSet()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn('somePassword');
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->urlManagerMock->expects($this->never())
            ->method('getUrls');
        $this->environmentMock->expects($this->never())
            ->method('getAdminUrl');
        $this->environmentMock->expects($this->never())
            ->method('getAdminEmail');
        $this->environmentMock->expects($this->never())
            ->method('getAdminUsername');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->mailFunctionMock->expects($this->never());
        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->passwordResetEmail->execute();
    }

    /**
     * @param string $adminUrl
     * @param string $adminUsername
     * @param string $expectedAdminUrl
     * @param string $expectedAdminUsername
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $adminUrl,
        $adminUsername,
        $expectedAdminUrl,
        $expectedAdminUsername
    ) {
        $adminEmail = 'admin@example.com';
        $url = 'https://localhost/';
        $dir = '/root';
        $file = $dir . '/var/credentials_email.txt';
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn('');
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($dir);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn(['secure' => ['' => $url]]);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUrl')
            ->willReturn($adminUrl);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($adminEmail);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($adminUsername);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Emailing admin URL to admin user ' . $expectedAdminUsername . ' at ' . $adminEmail],
                ['Saving email with admin URL: ' . $file]
            );
        $this->mailFunctionMock->expects($this->once())
            ->with(
                $this->stringContains($adminEmail),
                $this->anything(),
                $this->logicalAnd(
                    $this->stringContains($expectedAdminUsername),
                    $this->stringContains($adminEmail),
                    $this->stringContains($url . $expectedAdminUrl)
                )
            );
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $file,
                $this->logicalAnd(
                    $this->stringContains($expectedAdminUsername),
                    $this->stringContains($adminEmail),
                    $this->stringContains($url . $expectedAdminUrl)
                )
            );

        $this->passwordResetEmail->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            ['', '', Environment::DEFAULT_ADMIN_URL, Environment::DEFAULT_ADMIN_NAME],
            ['admino4ka', 'root', 'admino4ka', 'root'],
        ];
    }
}
