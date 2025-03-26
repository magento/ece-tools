<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Config\Validator\Deploy\PostDeploy;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var HookChecker|MockObject
     */
    private $hookCheckerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->hookCheckerMock = $this->createMock(HookChecker::class);

        $this->validator = new PostDeploy(
            $this->resultFactoryMock,
            $this->hookCheckerMock
        );
    }

    public function testValidate()
    {
        $this->hookCheckerMock->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWithError()
    {
        $this->hookCheckerMock->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('Your application does not have the "post_deploy" hook enabled.');

        $this->validator->validate();
    }
}
