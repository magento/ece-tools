<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\AdminEmail;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->adminEmailValidator = new AdminEmail(
            $this->environmentMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testValidate()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('admin@example.com');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->createMock(Result::class));

        $this->adminEmailValidator->validate();
    }

    /**
     * @inheritdoc
     */
    public function testValidateAdminEmailNotExist()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                'ADMIN_EMAIL not set during install!',
                'We need this variable set to send the password reset email. ' .
                'Please set ADMIN_EMAIL and retry deploy.'
            )
            ->willReturn($this->createMock(Result::class));


        $this->adminEmailValidator->validate();
    }
}
