<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UpgradeProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $step;

    /**
     * @var UpgradeProcess|MockObject
     */
    private $upgradeProcessMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->upgradeProcessMock = $this->createMock(UpgradeProcess::class);

        $this->step = new Setup(
            $this->flagManagerMock,
            $this->upgradeProcessMock
        );
    }

    /**
     * @throws StepException
     * @throws ConfigurationMismatchException
     */
    public function testExecute()
    {
        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->upgradeProcessMock->expects($this->exactly(1))
            ->method('execute');

        $this->step->execute();
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $this->flagManagerMock->expects($this->exactly(1))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->upgradeProcessMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Error during command execution');

        $this->step->execute();
    }
}
