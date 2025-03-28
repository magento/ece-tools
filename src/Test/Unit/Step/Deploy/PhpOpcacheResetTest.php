<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Service\Php;
use Magento\MagentoCloud\Step\Deploy\PhpOpcacheReset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PhpOpcacheResetTest extends TestCase
{
    /**
     * @var PhpOpcacheReset
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Php|MockObject
     */
    private $phpMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->phpMock = $this->createMock(Php::class);

        $this->step = new PhpOpcacheReset(
            $this->loggerMock,
            $this->phpMock
        );
    }

    /**
     * Opcache Cli Enabled
     */
    public function testExecuteOpcacheCliEnabled(): void
    {
        $this->phpMock->expects($this->once())
            ->method('isOpcacheCliEnabled')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Reset the contents of the opcache');
        $this->phpMock->expects($this->once())
            ->method('resetOpcache');
        $this->step->execute();
    }

    /**
     * Opcache Cli No Enabled
     */
    public function testExecuteOpcacheCliNoEnabled(): void
    {
        $this->phpMock->expects($this->once())
            ->method('isOpcacheCliEnabled')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->phpMock->expects($this->never())
            ->method('resetOpcache');

        $this->step->execute();
    }
}
