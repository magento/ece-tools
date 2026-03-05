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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * {@inheritdoc}
 */
#[AllowMockObjectsWithoutExpectations]
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
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->shellFactoryMock->method('create')
            ->with(ShellFactory::STRATEGY_MAGENTO_SHELL)
            ->willReturn($this->shellMock);

        $this->config = new System(
            $this->shellFactoryMock,
            $this->magentoVersionMock
        );
    }

    /**
     * Test validate method.
     *
     * @param mixed $expectedResult
     * @dataProvider getDataProvider
     * @return void
     * @throws UndefinedPackageException
     */
    #[DataProvider('getDataProvider')]
    public function testValidate($expectedResult): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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
     * Data provider for testValidate method.
     *
     * @return array
     */
    public static function getDataProvider(): array
    {
        return [
            ['some'],
            ['0'],
            ['1'],
        ];
    }

    /**
     * Test getDefaultValue method.
     *
     * @return void
     * @throws UndefinedPackageException
     */
    public function testGetDefaultValue(): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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
     * Test getLegacyVersion method.
     *
     * @return void
     * @throws UndefinedPackageException
     */
    public function testGetLegacyVersion(): void
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
     * Test getWithShellException method.
     *
     * @return void
     * @throws UndefinedPackageException
     */
    public function testGetWithShellException(): void
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
