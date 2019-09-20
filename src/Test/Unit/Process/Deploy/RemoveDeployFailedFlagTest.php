<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Process\Deploy\RemoveDeployFailedFlag;
use Magento\MagentoCloud\Process\ProcessException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RemoveDeployFailedFlagTest extends TestCase
{
    /**
     * @var RemoveDeployFailedFlag
     */
    private $process;

    /**
     * @var Manager|MockObject
     */
    private $flagManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->flagManager = $this->createMock(Manager::class);

        $this->process = new RemoveDeployFailedFlag(
            $this->flagManager
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute(): void
    {
        $this->flagManager->expects($this->once())
            ->method('delete')
            ->with(Manager::FLAG_DEPLOY_HOOK_IS_FAILED);

        $this->process->execute();
    }
}
