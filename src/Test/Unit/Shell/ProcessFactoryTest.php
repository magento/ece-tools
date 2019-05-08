<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Shell\Process;
use Magento\MagentoCloud\Shell\ProcessFactory;
use Magento\MagentoCloud\Shell\ProcessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ProcessFactoryTest extends TestCase
{
    /**
     * @var ProcessFactory
     */
    private $processFactory;

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

        $this->processFactory = new ProcessFactory($this->containerMock);
    }

    public function testCreate()
    {
        /** @var ProcessInterface|MockObject $processMock */
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $params = ['option' => 'value'];

        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Process::class, $params)
            ->willReturn($processMock);

        $this->processFactory->create($params);
    }
}
