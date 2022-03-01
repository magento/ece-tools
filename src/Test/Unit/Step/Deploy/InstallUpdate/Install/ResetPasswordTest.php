<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\ResetPassword;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;

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
     * @var AdminDataInterface|MockObject
     */
    private $adminDataMock;

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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->adminDataMock = $this->getMockForAbstractClass(AdminDataInterface::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->mailFunctionMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install',
            'mail'
        );

        $this->resetPassword = new ResetPassword(
            $this->loggerMock,
            $this->adminDataMock,
            $this->urlManagerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @param InvokedCount $expectsAdminEmail
     * @param string $dataAdminPassword
     * @param string $dataAdminEmail
     * @return void
     * @throws StepException
     *
     * @dataProvider executeWithPasswordSetOrAdminEmailNotSetDataProvider
     */
    public function testExecuteWithPasswordSetOrAdminEmailNotSet(
        $expectsAdminEmail,
        $dataAdminPassword,
        $dataAdminEmail
    ): void {
        $this->adminDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn($dataAdminPassword);
        $this->adminDataMock->expects($expectsAdminEmail)
            ->method('getEmail')
            ->willReturn($dataAdminEmail);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->urlManagerMock->expects($this->never())
            ->method('getUrls');
        $this->adminDataMock->expects($this->never())
            ->method('getUrl');
        $this->adminDataMock->expects($this->never())
            ->method('getUsername');
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
     * @param string $expectedContent
     * @throws StepException
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        string $adminUrl,
        string $adminUsername,
        string $expectedAdminUsername,
        string $expectedContent
    ): void {
        $adminEmail = 'admin@example.com';
        $url = 'https://localhost/';
        $dir = '/root';
        $file = $dir . '/var/credentials_email.txt';
        $this->adminDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn('');
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($dir);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn(['secure' => ['' => $url]]);
        $this->adminDataMock->expects($this->once())
            ->method('getUrl')
            ->willReturn($adminUrl);
        $this->adminDataMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn($adminEmail);
        $this->adminDataMock->expects($this->once())
            ->method('getUsername')
            ->willReturn($adminUsername);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Emailing admin URL to admin user ' . $expectedAdminUsername . ' at ' . $adminEmail],
                ['Saving email with admin URL: ' . $file]
            );
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('Hello {{ admin_url }} {{ admin_email }} {{ admin_name }}');
        $this->mailFunctionMock->expects($this->once())
            ->with(
                $this->stringContains($adminEmail),
                $this->anything(),
                $expectedContent
            );
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $file,
                $expectedContent
            );

        $this->resetPassword->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                '',
                AdminDataInterface::DEFAULT_ADMIN_URL,
                AdminDataInterface::DEFAULT_ADMIN_NAME,
                'Hello https://localhost/admin admin@example.com admin'
            ],
            ['admin', 'root', 'root', 'Hello https://localhost/admin admin@example.com root'],
            ['admin2', 'root', 'root', 'Hello https://localhost/admin2 admin@example.com root'],
        ];
    }

    /**
     * @throws StepException
     */
    public function testExceptionTemplateNotReadable()
    {
        $url = 'https://localhost/';
        $this->adminDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn('');
        $this->adminDataMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn('admin@example.com');
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn(['secure' => ['' => $url]]);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException('some error'));

        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_UNABLE_TO_READ_RESET_PASSWORD_TMPL);
        $this->expectExceptionMessage('some error');

        $this->resetPassword->execute();
    }

    public function testExceptionFileNotWritable()
    {
        $url = 'https://localhost/';
        $this->adminDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn('');
        $this->adminDataMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn('admin@example.com');
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn(['secure' => ['' => $url]]);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('Hello {{ admin_url }} {{ admin_email }} {{ admin_name }}');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->willThrowException(new FileSystemException('some error'));

        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_FILE_CREDENTIALS_EMAIL_NOT_WRITABLE);
        $this->expectExceptionMessage('some error');

        $this->resetPassword->execute();
    }
}
