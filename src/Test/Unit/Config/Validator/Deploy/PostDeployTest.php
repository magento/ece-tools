<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class PostDeployTest extends TestCase
{
    /**
     * @var PostDeploy
     */
    private $validator;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

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
        $this->environmentMock = $this->createMock(Environment::class);

        $this->validator = new PostDeploy(
            $this->resultFactoryMock,
            $this->environmentMock
        );
    }

    public function testValidate()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplication')
            ->willReturn([
                'hooks' => ['post_deploy' => 'some_hook'],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $this->assertInstanceOf(
            Success::class,
            $this->validator->validate()
        );
    }

    public function testValidateWithError()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplication')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::ERROR, [
                'error' => 'Your application does not have the \'post_deploy\' hook enabled.',
            ])
            ->willReturn($this->createMock(Error::class));

        $this->assertInstanceOf(
            Error::class,
            $this->validator->validate()
        );
    }
}
