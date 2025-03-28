<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Validator\Build\ModulesExists;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ModulesExistsTest extends TestCase
{
    /**
     * @var ModulesExists
     */
    private $validator;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new ModulesExists(
            $this->readerMock,
            $this->resultFactoryMock
        );
    }

    public function testValidate()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['modules' => ['Some_module' => 1]]);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWithoutModules()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('The modules section is missing from the shared config file.');

        $this->validator->validate();
    }
}
