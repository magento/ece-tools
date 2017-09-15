<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\AdminCredentials;
use Magento\MagentoCloud\Config\Environment;

/**
 * @inheritdoc
 */
class AdminCredentialsTest extends TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $adapterMock;

    /**
     * @var PasswordGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $passwordGeneratorMock;

    /**
     * @var AdminCredentials
     */
    private $adminCredentials;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->adapterMock = $this->createMock(Adapter::class);
        $this->passwordGeneratorMock = $this->createMock(PasswordGenerator::class);

        $this->adminCredentials = new AdminCredentials(
            $this->loggerMock,
            $this->environmentMock,
            $this->adapterMock,
            $this->passwordGeneratorMock
        );
    }

    public function testExecute()
    {
        $adminPassword = 'some password';
        $generatedPassword = 'generated password';
        $firstName = 'John';
        $lastName = 'Doe';
        $email = 'JohnDoe@example.com';
        $userName = 'admin';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating admin credentials.');
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn($adminPassword);
        $this->environmentMock->expects($this->once())
            ->method('getAdminFirstname')
            ->willReturn($firstName);
        $this->environmentMock->expects($this->once())
            ->method('getAdminLastname')
            ->willReturn($lastName);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($email);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($userName);
        $this->passwordGeneratorMock->expects($this->once())
            ->method('generate')
            ->with($adminPassword)
            ->willReturn($generatedPassword);
        $this->adapterMock->expects($this->once())
            ->method('execute')
            ->with(
                "update admin_user set firstname = '" . $firstName . "', lastname = '" . $lastName
                . "', email = '" . $email . "', username = '" . $userName . "', password='" . $generatedPassword
                . "' where user_id = '1';"
            );

        $this->adminCredentials->execute();
    }
}
