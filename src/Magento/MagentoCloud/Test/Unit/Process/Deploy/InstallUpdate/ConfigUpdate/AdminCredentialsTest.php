<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\DB\ConnectionInterface;
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
     * @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

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
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->passwordGeneratorMock = $this->createMock(PasswordGenerator::class);

        $this->adminCredentials = new AdminCredentials(
            $this->loggerMock,
            $this->connectionMock,
            $this->environmentMock,
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

        $query = 'UPDATE `admin_user` SET `firstname` = ?, `lastname` = ?, `email` = ?, `username` = ?, `password` = ?'
            . ' WHERE `user_id` = 1';

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
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                $query,
                [$firstName, $lastName, $email, $userName, $generatedPassword]
            );

        $this->adminCredentials->execute();
    }
}
