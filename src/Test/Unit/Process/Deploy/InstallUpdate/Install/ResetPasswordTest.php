<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\View\RendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\ResetPassword;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount as InvokedCountMatcher;

/**
 * @inheritdoc
 */
class ResetPasswordTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @var MockObject
     */
    private $mailFunctionMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var ResetPassword
     */
    private $resetPassword;

    /**
     * @var RendererInterface|MockObject
     */
    private $rendererMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->rendererMock = $this->getMockForAbstractClass(RendererInterface::class);

        $this->mailFunctionMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install',
            'mail'
        );

        $this->resetPassword = new ResetPassword(
            $this->loggerMock,
            $this->environmentMock,
            $this->urlManagerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->rendererMock
        );
    }

    /**
     * @param InvokedCountMatcher $expectsAdminEmail
     * @param string $dataAdminPassword
     * @param string $dataAdminEmail
     * @return void
     * @dataProvider executeWithPasswordSetOrAdminEmailNotSetDataProvider
     */
    public function testExecuteWithPasswordSetOrAdminEmailNotSet(
        $expectsAdminEmail,
        $dataAdminPassword,
        $dataAdminEmail
    ) {
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn($dataAdminPassword);
        $this->environmentMock->expects($expectsAdminEmail)
            ->method('getAdminEmail')
            ->willReturn($dataAdminEmail);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->urlManagerMock->expects($this->never())
            ->method('getUrls');
        $this->environmentMock->expects($this->never())
            ->method('getAdminUrl');
        $this->environmentMock->expects($this->never())
            ->method('getAdminUsername');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->mailFunctionMock->expects($this->never());
        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->resetPassword->execute();
    }

    /**
     * @return array
     */
    public function executeWithPasswordSetOrAdminEmailNotSetDataProvider(): array
    {
        return [
            [
                'expectsAdminEmail' => $this->never(),
                'dataAdminPassword' => 'somePassword',
                'dataAdminEmail' => ''
            ],
            [
                'expectsAdminEmail' => $this->once(),
                'dataAdminPassword' => '',
                'dataAdminEmail' => ''
            ],
        ];
    }

    /**
     * @param string $adminUrl
     * @param string $adminUsername
     * @param string $expectedAdminUsername
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $adminUrl,
        $adminUsername,
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
        $this->environmentMock->expects($this->exactly(2))
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
                'Some content'
            );
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $file,
                'Some content'
            );
        $this->rendererMock->expects($this->once())
            ->method('render')
            ->willReturn('Some content');

        $this->resetPassword->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            ['', '', Environment::DEFAULT_ADMIN_URL, Environment::DEFAULT_ADMIN_NAME],
            ['admin', 'root', 'root'],
        ];
    }
}
