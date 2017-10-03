<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SetupTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

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
     * @var Setup
     */
    private $process;

    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->setMethods(['getVerbosityLevel', 'getVariables', 'getRelationships'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new Setup(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->environmentMock,
            $this->shellMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->urlManagerMock->expects($this->once())
            ->method('getUnSecureUrls')
            ->willReturn(['' => 'http://unsecure.url']);
        $this->urlManagerMock->expects($this->once())
            ->method('getSecureUrls')
            ->willReturn(['' => 'https://secure.url']);
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn(' -v');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'database' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => '3306',
                        'path' => 'magento',
                        'username' => 'user',
                        'password' => 'password'
                    ]
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getVariables')
            ->willReturn([
                'ADMIN_URL' => 'admin_url',
                'ADMIN_LOCALE' => 'fr_FR',
                'ADMIN_FIRSTNAME' => 'Firstname',
                'ADMIN_LASTNAME' => 'Lastname',
            ]);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(
                "php ./bin/magento setup:install \
            --session-save=db \
            --cleanup-database \
            --currency=USD \
            --base-url=http://unsecure.url \
            --base-url-secure=https://secure.url \
            --language=fr_FR \
            --timezone=America/Los_Angeles \
            --db-host=localhost \
            --db-name=magento \
            --db-user=user \
            --backend-frontname=admin_url \
            --admin-user=admin \
            --admin-firstname=Firstname \
            --admin-lastname=Lastname \
            --admin-email=john@example.com \
            --admin-password=admin12 \
            --db-password=password -v"
            );

        $this->process->execute();
    }
}
