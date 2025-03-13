<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\Build\ApplyPatches;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $step;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->step = new ApplyPatches($this->managerMock);
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->managerMock->expects($this->once())
            ->method('apply');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConfigException(): void
    {
        $this->managerMock->expects($this->once())
            ->method('apply')
            ->willThrowException(new ConfigException('config not found', Error::BUILD_CONFIG_NOT_DEFINED));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('config not found');
        $this->expectExceptionCode(Error::BUILD_CONFIG_NOT_DEFINED);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException(): void
    {
        $this->managerMock->expects($this->once())
            ->method('apply')
            ->willThrowException(new ShellException('command failed'));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('command failed');
        $this->expectExceptionCode(Error::BUILD_PATCH_APPLYING_FAILED);

        $this->step->execute();
    }
}
