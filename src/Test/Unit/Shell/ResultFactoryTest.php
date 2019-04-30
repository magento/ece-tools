<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Shell\ResultFactory;
use Magento\MagentoCloud\Shell\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @inheritdoc
 */
class ResultFactoryTest extends TestCase
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->resultFactory = new ResultFactory($this->containerMock);
    }

    public function testCreate()
    {
        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(\Magento\MagentoCloud\Shell\Result::class, ['process' => $processMock])
            ->willReturn($this->getMockForAbstractClass(ResultInterface::class));

        $this->resultFactory->create($processMock);
    }
}
