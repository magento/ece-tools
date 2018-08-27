<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Validator\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\AdminCredentials;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminCredentials as AdminCredentialsValidator;

/**
 * @inheritdoc
 */
class AdminCredentialsTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var PasswordGenerator|MockObject
     */
    private $passwordGeneratorMock;

    /**
     * @var AdminCredentials
     */
    private $adminCredentials;

    /**
     * @var AdminCredentialsValidator|MockObject
     */
    private $adminCredentialsValidatorMock;

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
        $this->adminCredentialsValidatorMock = $this->createMock(AdminCredentialsValidator::class);

        $this->adminCredentials = new AdminCredentials(
            $this->loggerMock,
            $this->connectionMock,
            $this->environmentMock,
            $this->passwordGeneratorMock,
            $this->adminCredentialsValidatorMock
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

        $query = 'UPDATE `admin_user` SET `email` = ?, `username` = ?, `firstname` = ?, `lastname` = ?, `password` = ?'
            . ' ORDER BY `user_id` ASC LIMIT 1';

        $this->adminCredentialsValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());
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
            ->method('generateSaltAndHash')
            ->with($adminPassword)
            ->willReturn($generatedPassword);
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                $query,
                [$email, $userName, $firstName, $lastName, $generatedPassword]
            );
        $this->connectionMock->expects($this->exactly(2))
            ->method('select')
            ->willReturnMap([
                ['SELECT 1 FROM `admin_user` WHERE `email` = ?', [$email], []],
                ['SELECT 1 FROM `admin_user` WHERE `username` = ?', [$userName], []],
            ]);

        $this->adminCredentials->execute();
    }

    public function testExecuteEmailAndUsernameUsed()
    {
        $lastName = 'Doe';
        $email = 'JohnDoe@example.com';
        $userName = 'admin';

        $query = 'UPDATE `admin_user` SET `lastname` = ? ORDER BY `user_id` ASC LIMIT 1';

        $this->adminCredentialsValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating admin credentials.');
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminFirstname')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminLastname')
            ->willReturn($lastName);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($email);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($userName);
        $this->passwordGeneratorMock->expects($this->never())
            ->method('generateSaltAndHash');
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                $query,
                [$lastName]
            );
        $this->connectionMock->expects($this->exactly(2))
            ->method('select')
            ->willReturnMap([
                ['SELECT 1 FROM `admin_user` WHERE `email` = ?', [$email], ['1']],
                ['SELECT 1 FROM `admin_user` WHERE `username` = ?', [$userName], ['1']],
            ]);

        $this->adminCredentials->execute();
    }

    public function testExecuteWithoutChanges()
    {
        $this->adminCredentialsValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating admin credentials: nothing to update.');
        $this->environmentMock->expects($this->once())
            ->method('getAdminPassword')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminFirstname')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminLastname')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn('');
        $this->passwordGeneratorMock->expects($this->never())
            ->method('generateSaltAndHash');
        $this->connectionMock->expects($this->never())
            ->method('select');
        $this->connectionMock->expects($this->never())
            ->method('affectingQuery');

        $this->adminCredentials->execute();
    }

    public function testValidationFailed()
    {
        $this->adminCredentialsValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Error('validation error', 'validation suggestion'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping updating admin credentials: validation error (validation suggestion)');
        $this->connectionMock->expects($this->never())
            ->method('affectingQuery');

        $this->adminCredentials->execute();
    }
}
