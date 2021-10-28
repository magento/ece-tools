<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Magento\System;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * {@inheritdoc}
 */
class SystemTest extends TestCase
{
    /**
     * @var System
     */
    private $config;

    /**
     * @var ShellFactory|MockObject
     */
    private $shellFactoryMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellFactoryMock = $this->createMock(ShellFactory::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->shellFactoryMock->method('create')
            ->with(ShellFactory::STRATEGY_MAGENTO_SHELL)
            ->willReturn($this->shellMock);

        $this->config = new System(
            $this->shellFactoryMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @param mixed $expectedResult
     * @dataProvider getDataProvider
     * @throws UndefinedPackageException
     */
    public function testValidate($expectedResult)
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($expectedResult);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('config:show', ['some/key'])
            ->willReturn($processMock);

        $this->assertSame($expectedResult, $this->config->get('some/key'));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            ['some'],
            ['0'],
            ['1'],
        ];
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetDefaultValue()
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('config:show', ['some/key'])
            ->willReturn($processMock);

        $this->assertSame('', $this->config->get('some/key'));
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetLegacyVersion()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(false);
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->assertNull($this->config->get('some/key'));
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetWithShellException()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.0')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('config:show', ['some/key'])
            ->willThrowException(new ShellException('some error'));

        $this->assertNull($this->config->get('some/key'));
    }
}
