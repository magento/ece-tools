<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Deploy;
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
     * @var Deploy|Mock
     */
    private $deployMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->deployMock = $this->createMock(Deploy::class);

        $this->adminEmailValidator = new AdminEmail(
            $this->environmentMock,
            $this->resultFactoryMock,
            $this->deployMock
        );
    }

    public function testValidate()
    {
        $this->deployMock->expects($this->once())
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
        $this->deployMock->expects($this->once())
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
        $this->deployMock->expects($this->once())
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
