<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminCredentials;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class AdminCredentialsTest extends TestCase
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var State|Mock
     */
    private $stateMock;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->stateMock = $this->createMock(State::class);
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->validator = new AdminCredentials(
            $this->resultFactoryMock,
            $this->stateMock,
            $this->connectionMock,
            $this->environmentMock
        );
    }

    public function testValidate()
    {
        $email = 'test@email.com';
        $username = 'admin';

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($email);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($username);
        $this->connectionMock->expects($this->exactly(2))
            ->method('count')
            ->willReturnMap([
                [
                    'SELECT 1 FROM `admin_user` WHERE `email` = ?',
                    [$email],
                    0,
                ],
                [
                    'SELECT 1 FROM `admin_user` WHERE `username` = ?',
                    [$username],
                    0,
                ],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(Success::SUCCESS)
            ->willReturn(new Success());

        $this->validator->validate();
    }

    public function testValidateDuplicateEmail()
    {
        $email = 'test@email.com';
        $username = 'admin';

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($email);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($username);
        $this->connectionMock->expects($this->exactly(2))
            ->method('count')
            ->willReturnMap([
                [
                    'SELECT 1 FROM `admin_user` WHERE `email` = ?',
                    [$email],
                    1,
                ],
                [
                    'SELECT 1 FROM `admin_user` WHERE `username` = ?',
                    [$username],
                    0,
                ],
            ]);
        $this->connectionMock->expects($this->once())
            ->method('selectOne')
            ->with('SELECT `email`, `username` FROM `admin_user` ORDER BY `user_id` ASC LIMIT 1')
            ->willReturn([
                'email' => 'old_email@email.com',
                'username' => 'admin',
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(Error::ERROR, [
                'error' => 'The same email is already used by different admin',
                'suggestion' => 'Use different email address',
            ])
            ->willReturn(new Error('some_error'));

        $this->validator->validate();
    }

    public function testValidateDuplicateUsername()
    {
        $email = 'test@email.com';
        $username = 'admin';

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn($email);
        $this->environmentMock->expects($this->once())
            ->method('getAdminUsername')
            ->willReturn($username);
        $this->connectionMock->expects($this->exactly(2))
            ->method('count')
            ->willReturnMap([
                [
                    'SELECT 1 FROM `admin_user` WHERE `email` = ?',
                    [$email],
                    0,
                ],
                [
                    'SELECT 1 FROM `admin_user` WHERE `username` = ?',
                    [$username],
                    1,
                ],
            ]);
        $this->connectionMock->expects($this->once())
            ->method('selectOne')
            ->with('SELECT `email`, `username` FROM `admin_user` ORDER BY `user_id` ASC LIMIT 1')
            ->willReturn([
                'email' => 'email@email.com',
                'username' => 'old_admin',
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(Error::ERROR, [
                'error' => 'The same username is already used by different admin',
                'suggestion' => 'Use different username',
            ])
            ->willReturn(new Error('some_error'));

        $this->validator->validate();
    }

    public function testValidateNotInstalled()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->connectionMock->expects($this->never())
            ->method('count');

        $this->validator->validate();
    }
}
