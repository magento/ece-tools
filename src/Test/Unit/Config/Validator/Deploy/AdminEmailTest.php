<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\AdminEmail;
use Magento\MagentoCloud\Config\Validator\Result;
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
                'The variable ADMIN_EMAIL was not set during the installation.' .
                ' This variable is required to send the Admin password reset email.',
                'Set an environment variable for ADMIN_EMAIL and retry deployment.'
            )
            ->willReturn($this->createMock(Result::class));

        $this->adminEmailValidator->validate();
    }
}
