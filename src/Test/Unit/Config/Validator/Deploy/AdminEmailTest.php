<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminEmail;
use Magento\MagentoCloud\Config\Validator\Deploy\DatabaseConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class AdminEmailTest extends TestCase
{
    /**
     * @var AdminEmail
     */
    private $adminEmailValidator;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var DatabaseConfiguration|Mock
     */
    private $databaseConfigurationMock;

    /**
     * @var State|Mock
     */
    private $stateMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->databaseConfigurationMock = $this->createMock(DatabaseConfiguration::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->stateMock = $this->createMock(State::class);

        $this->adminEmailValidator = new AdminEmail(
            $this->databaseConfigurationMock,
            $this->environmentMock,
            $this->resultFactoryMock,
            $this->stateMock
        );
    }

    public function testValidate()
    {
        $this->stateMock->expects($this->never())
            ->method('isInstalled');
        $this->databaseConfigurationMock->expects($this->never())
            ->method('validate');
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('admin@example.com');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $result = $this->adminEmailValidator->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    public function testValidateMagentoInstalled()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->databaseConfigurationMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $result = $this->adminEmailValidator->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    public function testValidateWrongDatabaseConnection()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->databaseConfigurationMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Error::class));
        $this->stateMock->expects($this->never())
            ->method('isInstalled');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $result = $this->adminEmailValidator->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    public function testValidateAdminEmailNotExist()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->databaseConfigurationMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'The variable ADMIN_EMAIL was not set during the installation.',
                    'suggestion' => 'This variable is required to send the Admin password reset email.' .
                        ' Set an environment variable for ADMIN_EMAIL and retry deployment.'
                ]
            )
            ->willReturn($this->createMock(Error::class));

        $result = $this->adminEmailValidator->validate();

        $this->assertInstanceOf(Error::class, $result);
    }
}
