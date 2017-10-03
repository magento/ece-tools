<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\SendPasswordResetEmail;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SendPasswordResetEmailTest extends TestCase
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
     * @var SendPasswordResetEmail
     */
    private $sendPasswordResetEmail;

    /**
     * @var Mock
     */
    private $mailFunctionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);

        $this->mailFunctionMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install',
            'mail'
        );

        $this->sendPasswordResetEmail = new SendPasswordResetEmail(
            $this->loggerMock,
            $this->environmentMock,
            $this->urlManagerMock
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

        $this->sendPasswordResetEmail->execute();
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
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn('');
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
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Emailing admin URL to admin user ' . $expectedAdminUsername . ' at ' . $adminEmail);
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

        $this->sendPasswordResetEmail->execute();
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
