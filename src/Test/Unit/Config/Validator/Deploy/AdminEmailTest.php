<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminEmail;
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
     * @var State|Mock
     */
    private $stateMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->stateMock = $this->createMock(State::class);

        $this->adminEmailValidator = new AdminEmail(
            $this->environmentMock,
            $this->resultFactoryMock,
            $this->stateMock
        );
    }

    public function testValidate()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
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
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->environmentMock->expects($this->never())
            ->method('getAdminEmail');
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
