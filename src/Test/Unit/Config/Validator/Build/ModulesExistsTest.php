<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\ConfigInterface;
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
     * @var ConfigInterface|MockObject
     */
    private $configMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new ModulesExists(
            $this->configMock,
            $this->resultFactoryMock
        );
    }

    public function testValidate()
    {
        $this->configMock->expects($this->once())
            ->method('has')
            ->with('modules')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWithoutModules()
    {
        $this->configMock->expects($this->once())
            ->method('has')
            ->with('modules')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('The modules section is missing from the shared config file.');

        $this->validator->validate();
    }
}
