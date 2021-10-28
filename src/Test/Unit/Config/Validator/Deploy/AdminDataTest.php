<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminData;
use Magento\MagentoCloud\Config\Validator\Deploy\DatabaseConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AdminDataTest extends TestCase
{
    /**
     * @var State|MockObject
     */
    private $stateMock;

    /**
     * @var AdminDataInterface|MockObject
     */
    private $adminDataMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var DatabaseConfiguration|MockObject
     */
    private $databaseConfigurationMock;

    /**
     * @var AdminData
     */
    private $adminData;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stateMock = $this->createMock(State::class);
        $this->adminDataMock = $this->getMockForAbstractClass(AdminDataInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->databaseConfigurationMock = $this->createMock(DatabaseConfiguration::class);

        $this->adminData = new AdminData(
            $this->stateMock,
            $this->adminDataMock,
            $this->databaseConfigurationMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @param string $email
     * @param string $login
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     * @param bool $isInstalled
     * @param string $expectedMessage
     * @param string $expectedSuggestion
     * @param $expectedError
     * @param $expectedSuccess
     * @dataProvider validateDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testValidate(
        string $email,
        string $login,
        string $password,
        string $firstname,
        string $lastname,
        bool $isInstalled,
        string $expectedMessage,
        string $expectedSuggestion,
        $expectedError,
        $expectedSuccess
    ) {
        $this->databaseConfigurationMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->adminDataMock->expects($this->atLeastOnce())
            ->method('getEmail')
            ->willReturn($email);
        $this->adminDataMock->expects($this->once())
            ->method('getUsername')
            ->willReturn($login);
        $this->adminDataMock->expects($this->once())
            ->method('getFirstName')
            ->willReturn($firstname);
        $this->adminDataMock->expects($this->once())
            ->method('getLastName')
            ->willReturn($lastname);
        $this->adminDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn($password);
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn($isInstalled);

        $this->resultFactoryMock->expects($expectedError)
            ->method('error')
            ->with($expectedMessage, $expectedSuggestion);
        $this->resultFactoryMock->expects($expectedSuccess)
            ->method('success');

        $this->adminData->validate();
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            'Checks when Magento is installed without any admin data' => [
                'email' => '',
                'login' => '',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'isInstalled' => true,
                'expectedMessage' => '',
                'expectedSuggestion' => '',
                'expectedError' => $this->never(),
                'expectedSuccess' => $this->once()
            ],
            'Checks when Magento is not installed without any admin data' => [
                'email' => '',
                'login' => '',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'isInstalled' => false,
                'expectedMessage' => '',
                'expectedSuggestion' => '',
                'expectedError' => $this->never(),
                'expectedSuccess' => $this->once()
            ],
            'Checks when Magento is not installed with only admin email' => [
                'email' => 'admin@example.com',
                'login' => '',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'isInstalled' => false,
                'expectedMessage' => '',
                'expectedSuggestion' => '',
                'expectedError' => $this->never(),
                'expectedSuccess' => $this->once()
            ],
            'Checks when Magento is installed with only admin email' => [
                'email' => 'admin@example.com',
                'login' => '',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'isInstalled' => true,
                'expectedMessage' => 'The following admin data is required to create an admin user during initial'
                    . ' installation only and is ignored during upgrade process: admin email',
                'expectedSuggestion' => '',
                'expectedError' => $this->once(),
                'expectedSuccess' => $this->never()
            ],
            'Checks when Magento is not installed with some admin data except email' => [
                'email' => '',
                'login' => 'mylogin',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'isInstalled' => false,
                'expectedMessage' => 'The following admin data was ignored and an admin was not created because admin'
                    . ' email is not set: admin login',
                'expectedSuggestion' => 'Create an admin user via ssh manually: bin/magento admin:user:create',
                'expectedError' => $this->once(),
                'expectedSuccess' => $this->never()
            ],
        ];
    }
}
